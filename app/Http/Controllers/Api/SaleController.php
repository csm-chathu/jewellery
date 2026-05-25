<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\GoldRate;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\PrivateSale;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\ShopSetting;
use App\Services\SmsService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SaleController extends Controller
{
    public function index()
    {
        $user = request()->user();
        $sales = Sale::with(['customer:id,name', 'user:id,name', 'journalEntry:id,entry_number'])
            ->when(!$user->isAdmin(), fn($q) => $q->where('branch_id', $user->branch_id))
            ->when(request('search'), fn($q, $s) => $q->where('invoice_number', 'like', "%$s%"))
            ->when(request('customer_id'), fn($q, $c) => $q->where('customer_id', $c))
            ->when(request('status'), fn($q, $s) => $q->where('payment_status', $s))
            ->when(request('sale_type'), fn($q, $s) => $q->where('sale_type', $s))
            ->when(request('date_from'), fn($q, $d) => $q->whereDate('sold_at', '>=', $d))
            ->when(request('date_to'), fn($q, $d) => $q->whereDate('sold_at', '<=', $d))
            ->latest('sold_at')
            ->paginate(request('per_page', 20));
        return response()->json($sales);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'customer_id'              => 'nullable|exists:customers,id',
            'items'                    => 'required|array|min:1',
            'items.*.product_id'       => 'required|exists:products,id',
            'items.*.quantity'         => 'required|integer|min:1',
            'items.*.unit_price'       => 'required|numeric|min:0',
            'items.*.discount'         => 'nullable|numeric|min:0',
            'items.*.making_charge'    => 'nullable|numeric|min:0',
            'items.*.wastage_amount'   => 'nullable|numeric|min:0',
            'items.*.gold_value'       => 'nullable|numeric|min:0',
            'items.*.gemstone_value'   => 'nullable|numeric|min:0',
            'discount'                 => 'nullable|numeric|min:0',
            'tax'                      => 'nullable|numeric|min:0',
            'tax_rate'                 => 'nullable|numeric|min:0|max:100',
            'payment_method'           => 'required|in:cash,card,bank_transfer,cheque,other',
            'payment_status'           => 'required|in:pending,paid,partial,refunded',
            'sale_type'                => 'nullable|in:instant,booking',
            'booking_expires_at'       => 'nullable|date',
            'amount_paid'              => 'required|numeric|min:0',
            'notes'                    => 'nullable|string',
            'sold_at'                  => 'nullable|date',
        ]);

        DB::beginTransaction();
        try {
            $goldRates = GoldRate::todayRatesByLabel(); // ['24k' => GoldRate, ...]
            $subtotal         = 0;
            $officialSubtotal = 0;
            $goldTotal = 0; $gemTotal = 0; $mcTotal = 0; $wasteTotal = 0;

            // Pre-validate items
            $itemData = [];
            foreach ($data['items'] as $item) {
                $product = Product::findOrFail($item['product_id']);
                if (!$request->user()->isAdmin() && $product->branch_id !== $request->user()->branch_id) {
                    throw new \Exception("Product not available for your branch: {$product->name}");
                }
                if ($product->stock_quantity < $item['quantity']) {
                    throw new \Exception("Insufficient stock for: {$product->name}");
                }

                $qty         = $item['quantity'];
                $displayPrice = (float) $item['unit_price']; // what user charged — printed on invoice
                $itemDisc    = $item['discount'] ?? 0;

                // Resolve today's rate for this product's karat
                $karatKey  = strtolower($product->karat ?? '24k');
                $karatRate = $goldRates[$karatKey] ?? null;
                $rate24k   = $goldRates['24k'] ?? null;
                $ratePerGram = $karatRate?->rate_per_gram
                    ?? ($rate24k ? $rate24k->rate_per_gram * GoldRate::purityForLabel($karatKey) : null);

                // official unit_price = karat-rate amount per piece (what goes on the official record)
                // only cap down when displayPrice exceeds the auto-rate for a gold product
                $officialUnitPrice = $displayPrice;
                if ($ratePerGram && $product->weight && $product->karat) {
                    $autoRatePerPiece = round((float) $ratePerGram * (float) $product->weight, 2);
                    if ($displayPrice > $autoRatePerPiece) {
                        $officialUnitPrice = $autoRatePerPiece;
                    }
                }

                // Making charge
                $mc = $item['making_charge'] ?? 0;
                if ($mc == 0 && $product->making_charge > 0) {
                    $mc = match($product->making_charge_type) {
                        'per_gram'   => $product->making_charge * ($product->weight ?? 0) * $qty,
                        'per_piece'  => $product->making_charge * $qty,
                        'percentage' => ($displayPrice * $qty) * ($product->making_charge / 100),
                        default      => 0,
                    };
                }

                // Wastage
                $wastage = $item['wastage_amount'] ?? 0;
                if ($wastage == 0 && $product->wastage_percent > 0 && $product->weight) {
                    $wastage = $ratePerGram
                        ? round($ratePerGram * ($product->weight * $product->wastage_percent / 100) * $qty, 2)
                        : 0;
                }

                // Gold value — always auto-calculated from karat rate (not user-entered)
                $goldV = 0;
                if ($ratePerGram && $product->weight && $product->karat) {
                    $goldV = round((float) $ratePerGram * (float) $product->weight, 2) * $qty;
                }
                if ($goldV == 0) {
                    $goldV = $item['gold_value'] ?? 0; // fallback when no rate exists
                }

                // Gemstone value
                $gemV = $item['gemstone_value'] ?? 0;

                // Line total based on display price (what customer pays / prints on invoice)
                $lineTotal = ($displayPrice * $qty) - $itemDisc;
                // Official line total based on karat rate only (what goes into GL)
                $officialLineTotal = ($officialUnitPrice * $qty) - $itemDisc;

                $subtotal         += $lineTotal;
                $officialSubtotal += $officialLineTotal;
                $goldTotal        += $goldV;
                $gemTotal         += $gemV;
                $mcTotal          += $mc;
                $wasteTotal       += $wastage;

                $itemData[] = compact(
                    'product', 'qty', 'displayPrice', 'officialUnitPrice',
                    'itemDisc', 'lineTotal', 'goldV', 'gemV', 'mc', 'wastage'
                );
            }

            $discount      = $data['discount'] ?? 0;
            $tax           = $data['tax'] ?? 0;
            $total         = $subtotal - $discount + $tax;
            $officialTotal = round($officialSubtotal - $discount + $tax, 2);
            $amountPaid = (float) ($data['amount_paid'] ?? 0);

            $saleType = $data['sale_type'] ?? 'instant';
            $soldAt = !empty($data['sold_at']) ? Carbon::parse($data['sold_at']) : now();
            $bookingExpiresAt = null;
            $deliveryStatus = 'delivered';
            $deliveredAt = $soldAt;

            if ($saleType === 'booking') {
                if (empty($data['customer_id'])) {
                    throw new \Exception('Customer is required for booking sales.');
                }

                $bookingExpiresAt = !empty($data['booking_expires_at'])
                    ? Carbon::parse($data['booking_expires_at'])
                    : $soldAt->copy()->addMonths(3);

                if ($bookingExpiresAt->gt($soldAt->copy()->addMonths(3))) {
                    throw new \Exception('Booking expiry must be within 3 months from sale date.');
                }

                $deliveryStatus = 'booked';
                $deliveredAt = null;
            }

            if (!$request->user()->isAdmin() && !empty($data['customer_id'])) {
                $customer = Customer::findOrFail($data['customer_id']);
                if ($customer->branch_id !== $request->user()->branch_id) {
                    throw new \Exception('Selected customer does not belong to your branch.');
                }
            }

            $sale = Sale::create([
                'branch_id'             => $request->user()->branch_id,
                'invoice_number'        => 'INV-' . now()->format('Ymd') . '-' . str_pad(Sale::whereDate('created_at', today())->count() + 1, 4, '0', STR_PAD_LEFT),
                'customer_id'           => $data['customer_id'] ?? null,
                'user_id'               => $request->user()->id,
                'subtotal'              => $subtotal,
                'discount'              => $discount,
                'tax'                   => $tax,
                'tax_rate'              => $data['tax_rate'] ?? 0,
                'total'                 => $total,
                'official_total'        => $officialTotal,
                'gold_value_total'      => $goldTotal,
                'gemstone_value_total'  => $gemTotal,
                'making_charges_total'  => $mcTotal,
                'wastage_total'         => $wasteTotal,
                'payment_method'        => $data['payment_method'],
                'payment_status'        => $data['payment_status'],
                'sale_type'             => $saleType,
                'delivery_status'       => $deliveryStatus,
                'booking_expires_at'    => $bookingExpiresAt,
                'delivered_at'          => $deliveredAt,
                'amount_paid'           => $amountPaid,
                'notes'                 => $data['notes'] ?? null,
                'sold_at'               => $soldAt,
            ]);

            $totalExcess = 0;
            foreach ($itemData as $i) {
                SaleItem::create([
                    'sale_id'        => $sale->id,
                    'product_id'     => $i['product']->id,
                    'quantity'       => $i['qty'],
                    'unit_price'     => $i['officialUnitPrice'], // karat-rate amount (official)
                    'display_price'  => $i['displayPrice'],      // actual charged (for invoice)
                    'discount'       => $i['itemDisc'],
                    'total'          => $i['lineTotal'],
                    'gold_value'     => $i['goldV'],
                    'gemstone_value' => $i['gemV'],
                    'making_charge'  => $i['mc'],
                    'wastage_amount' => $i['wastage'],
                ]);
                $i['product']->decrement('stock_quantity', $i['qty']);

                // Accumulate excess above karat rate → private cashbook
                if ($i['displayPrice'] > $i['officialUnitPrice']) {
                    $totalExcess += ($i['displayPrice'] - $i['officialUnitPrice']) * $i['qty'];
                }
            }

            if ($totalExcess > 0.001) {
                $pMethod = in_array($data['payment_method'], ['cash', 'bank_transfer'])
                    ? $data['payment_method'] : 'cash';
                PrivateSale::create([
                    'sale_date'      => $soldAt->toDateString(),
                    'buyer_name'     => $sale->customer?->name,
                    'description'    => "Excess sale income — {$sale->invoice_number}",
                    'item_type'      => 'jewelry',
                    'gross_weight'   => 0,
                    'net_weight'     => 0,
                    'declared_karat' => 'mixed',
                    'rate_per_gram'  => 0,
                    'total_amount'   => round($totalExcess, 2),
                    'payment_method' => $pMethod,
                    'recorded_by'    => $request->user()->id,
                    'branch_id'      => $request->user()->branch_id,
                ]);
            }

            if ($saleType === 'instant') {
                $totalDiscount = collect($itemData)->sum('itemDisc') + $discount;
                $entry = $this->postInstantSaleJournal($sale, $officialTotal, $totalDiscount);
                $sale->update(['journal_entry_id' => $entry->id]);
            } elseif ($amountPaid > 0) {
                $entry = $this->postBookingAdvanceJournal($sale);
                $sale->update(['journal_entry_id' => $entry->id]);
            }

            AuditLog::record('sale_created', "Sale {$sale->invoice_number} — LKR {$total}", $sale);

            DB::commit();
            return response()->json($sale->load(['items.product', 'customer', 'user']), 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function show(Sale $sale)
    {
        $this->authorizeBranch($sale->branch_id);
        return response()->json($sale->load(['items.product', 'customer', 'user', 'journalEntry']));
    }

    public function settleBooking(Request $request, Sale $sale)
    {
        $this->authorizeBranch($sale->branch_id);

        if ($sale->sale_type !== 'booking') {
            return response()->json(['message' => 'Only booking sales can be settled with this endpoint.'], 422);
        }

        if ($sale->delivery_status !== 'booked') {
            return response()->json(['message' => 'This booking is already delivered or cancelled.'], 422);
        }

        if ($sale->booking_expires_at && now()->startOfDay()->gt($sale->booking_expires_at)) {
            return response()->json(['message' => 'Booking period has expired. Please create a new sale.'], 422);
        }

        $data = $request->validate([
            'payment_method' => 'required|in:cash,card,bank_transfer,cheque,other',
            'payment_amount' => 'required|numeric|min:0',
            'delivered_at'   => 'nullable|date',
            'notes'          => 'nullable|string|max:1000',
        ]);

        $remaining = round(max(0, $sale->total - $sale->amount_paid), 2);
        $paymentAmount = round((float) $data['payment_amount'], 2);

        if (abs($paymentAmount - $remaining) > 0.01) {
            return response()->json(['message' => "Payment amount must equal remaining balance (LKR {$remaining})."], 422);
        }

        DB::beginTransaction();
        try {
            $entry = $this->postBookingSettlementJournal($sale, $paymentAmount, $data['payment_method']);

            $sale->update([
                'amount_paid'     => $sale->total,
                'payment_status'  => 'paid',
                'payment_method'  => $data['payment_method'],
                'delivery_status' => 'delivered',
                'delivered_at'    => !empty($data['delivered_at']) ? Carbon::parse($data['delivered_at']) : now(),
                'journal_entry_id'=> $entry->id,
                'notes'           => $data['notes'] ?? $sale->notes,
            ]);

            AuditLog::record('sale_booking_settled', "Booking {$sale->invoice_number} settled and delivered", $sale);
            DB::commit();

            return response()->json($sale->fresh(['items.product', 'customer', 'user', 'journalEntry']));
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /** Public receipt view — no auth required, keyed by view_token */
    public function publicView(string $token)
    {
        $sale = Sale::with(['items.product:id,name,sku,karat,weight', 'customer:id,name,phone,email'])
            ->where('view_token', $token)
            ->firstOrFail();

        $shop = ShopSetting::first();

        return response()->json([
            'sale' => $sale,
            'shop' => $shop ? [
                'shop_name' => $shop->shop_name,
                'address'   => $shop->address,
                'phone'     => $shop->phone,
                'logo_url'  => $shop->logo_url,
            ] : [],
        ]);
    }

    /** Send (or resend) receipt SMS — phone can be overridden in the request body */
    public function sendSms(Request $request, Sale $sale)
    {
        $data = $request->validate([
            'phone'   => 'nullable|string|max:20',
            'message' => 'nullable|string|max:500',
        ]);

        // Resolve phone: use override → customer phone → error
        $phone = trim($data['phone'] ?? '');
        if (!$phone && $sale->customer_id) {
            $phone = optional($sale->customer ?? $sale->load('customer')->customer)->phone ?? '';
        }
        if (!$phone) {
            return response()->json(['message' => 'Please enter a phone number to send the SMS to.'], 422);
        }

        $shopName = optional(ShopSetting::first())->shop_name ?? 'Our Shop';
        $viewUrl  = rtrim(config('app.url'), '/') . '/receipt/' . $sale->view_token;
        $saleType = $sale->sale_type === 'booking' ? 'Booking advance' : 'Invoice';
        $custName = $sale->customer->name ?? 'Customer';

        $message = $data['message'] ?? (
            "Dear {$custName}, {$saleType} {$sale->invoice_number} of LKR " .
            number_format($sale->total, 2) . " has been created. " .
            "View receipt: {$viewUrl} . Thank you! - {$shopName}"
        );

        $smsService = app(SmsService::class);
        $result     = $smsService->sendSingle($phone, $message);

        if (!$result['success']) {
            return response()->json(['message' => 'SMS could not be delivered. Check SMS gateway settings.'], 422);
        }

        return response()->json(['message' => 'SMS sent successfully.', 'phone' => $phone]);
    }

    public function destroy(Sale $sale)
    {
        $this->authorizeBranch($sale->branch_id);
        if (!request()->user()->canDeleteTransactions()) {
            abort(403, 'You do not have permission to delete transactions.');
        }
        DB::beginTransaction();
        try {
            foreach ($sale->items as $item) {
                $item->product->increment('stock_quantity', $item->quantity);
            }

            // Delete the linked GL journal entry and its lines
            if ($sale->journal_entry_id && $je = $sale->journalEntry()->withTrashed()->first()) {
                $je->lines()->delete();
                $je->forceDelete();
            }

            AuditLog::record('sale_deleted', "Sale {$sale->invoice_number} deleted, stock restored, GL entry removed", $sale,
                ['invoice' => $sale->invoice_number, 'total' => $sale->total]);
            $sale->delete();
            DB::commit();
            return response()->json(['message' => 'Sale deleted, stock restored and GL entry removed']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    private function authorizeBranch(?int $branchId): void
    {
        $user = request()->user();
        if (!$user->isAdmin() && $user->branch_id !== $branchId) {
            abort(403, 'Forbidden for this branch.');
        }
    }

    private function paymentAccountByMethod(string $method): ?Account
    {
        if ($method === 'cash') {
            return Account::where('code', '1000')->first();
        }

        if (in_array($method, ['bank_transfer', 'card', 'cheque', 'other'])) {
            return Account::where('code', '1010')->first();
        }

        return null;
    }

    private function nextEntryNumber(): string
    {
        $seq = JournalEntry::whereDate('created_at', today())->withTrashed()->count() + 1;
        return 'JE-' . date('Ymd') . '-' . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }

    private function postInstantSaleJournal(Sale $sale, float $officialTotal, float $totalDiscount = 0): JournalEntry
    {
        $revenue     = Account::where('code', '4000')->first();
        $receivable  = Account::where('code', '1100')->first();
        $discountAcc = $totalDiscount > 0 ? Account::where('code', '4010')->first() : null;
        $paidAccount = $this->paymentAccountByMethod($sale->payment_method);

        if (!$revenue) {
            throw new \Exception('Revenue account (4000) is missing.');
        }

        // GL uses officialTotal (karat-rate price) — excess above karat rate is off-books (private cashbook)
        $paid = round(min($sale->amount_paid, $officialTotal), 2);
        $due  = round(max(0, $officialTotal - $paid), 2);

        if ($paid > 0 && !$paidAccount) {
            throw new \Exception('Payment account could not be resolved for this method.');
        }
        if ($due > 0 && !$receivable) {
            throw new \Exception('Accounts receivable account (1100) is missing.');
        }

        $entry = JournalEntry::create([
            'entry_number'   => $this->nextEntryNumber(),
            'entry_date'     => $sale->sold_at,
            'description'    => "Sales invoice {$sale->invoice_number}",
            'reference_type' => 'Sale',
            'reference_id'   => $sale->id,
            'branch_id'      => $sale->branch_id,
            'created_by'     => auth()->id(),
            'status'         => 'posted',
        ]);

        if ($paid > 0) {
            JournalEntryLine::create([
                'journal_entry_id' => $entry->id,
                'account_id'       => $paidAccount->id,
                'debit'            => $paid,
                'credit'           => 0,
                'description'      => 'Cash/Bank received for sale',
            ]);
        }

        if ($due > 0) {
            JournalEntryLine::create([
                'journal_entry_id' => $entry->id,
                'account_id'       => $receivable->id,
                'debit'            => $due,
                'credit'           => 0,
                'description'      => 'Accounts receivable for sale',
            ]);
        }

        // DR Sales Discounts for any discount given — gross revenue credited below
        if ($totalDiscount > 0 && $discountAcc) {
            JournalEntryLine::create([
                'journal_entry_id' => $entry->id,
                'account_id'       => $discountAcc->id,
                'debit'            => round($totalDiscount, 2),
                'credit'           => 0,
                'description'      => 'Sales discount given',
            ]);
        }

        // CR Revenue at gross amount (before discounts) so discount is visible separately
        $grossRevenue = round($officialTotal + ($discountAcc ? $totalDiscount : 0), 2);
        JournalEntryLine::create([
            'journal_entry_id' => $entry->id,
            'account_id'       => $revenue->id,
            'debit'            => 0,
            'credit'           => $grossRevenue,
            'description'      => 'Sales revenue',
        ]);

        return $entry;
    }

    private function postBookingAdvanceJournal(Sale $sale): JournalEntry
    {
        $deposit = Account::where('code', '2200')->first();
        $paidAccount = $this->paymentAccountByMethod($sale->payment_method);

        if (!$deposit || !$paidAccount) {
            throw new \Exception('Required accounts for booking advance (2200 and payment account) are missing.');
        }

        $entry = JournalEntry::create([
            'entry_number'   => $this->nextEntryNumber(),
            'entry_date'     => $sale->sold_at,
            'description'    => "Advance received for booking {$sale->invoice_number}",
            'reference_type' => 'SaleBookingAdvance',
            'reference_id'   => $sale->id,
            'branch_id'      => $sale->branch_id,
            'created_by'     => auth()->id(),
            'status'         => 'posted',
        ]);

        JournalEntryLine::create([
            'journal_entry_id' => $entry->id,
            'account_id'       => $paidAccount->id,
            'debit'            => $sale->amount_paid,
            'credit'           => 0,
            'description'      => 'Advance received from customer',
        ]);

        JournalEntryLine::create([
            'journal_entry_id' => $entry->id,
            'account_id'       => $deposit->id,
            'debit'            => 0,
            'credit'           => $sale->amount_paid,
            'description'      => 'Customer deposit liability',
        ]);

        return $entry;
    }

    private function postBookingSettlementJournal(Sale $sale, float $paymentAmount, string $paymentMethod): JournalEntry
    {
        $deposit     = Account::where('code', '2200')->first();
        $revenue     = Account::where('code', '4000')->first();
        $paidAccount = $this->paymentAccountByMethod($paymentMethod);

        if (!$deposit || !$revenue || !$paidAccount) {
            throw new \Exception('Required accounts for booking settlement are missing.');
        }

        // GL uses official total (karat-rate based); excess stays off-books in private cashbook
        $officialTotal     = $this->calculateOfficialTotal($sale);
        $officialAdvance   = round(min($sale->amount_paid, $officialTotal), 2);
        $officialFinalPaid = round(max(0, $officialTotal - $officialAdvance), 2);

        $entry = JournalEntry::create([
            'entry_number'   => $this->nextEntryNumber(),
            'entry_date'     => now(),
            'description'    => "Booking settled {$sale->invoice_number}",
            'reference_type' => 'SaleBookingSettlement',
            'reference_id'   => $sale->id,
            'branch_id'      => $sale->branch_id,
            'created_by'     => auth()->id(),
            'status'         => 'posted',
        ]);

        if ($officialFinalPaid > 0) {
            JournalEntryLine::create([
                'journal_entry_id' => $entry->id,
                'account_id'       => $paidAccount->id,
                'debit'            => $officialFinalPaid,
                'credit'           => 0,
                'description'      => 'Final payment received for booking',
            ]);
        }

        if ($officialAdvance > 0) {
            JournalEntryLine::create([
                'journal_entry_id' => $entry->id,
                'account_id'       => $deposit->id,
                'debit'            => $officialAdvance,
                'credit'           => 0,
                'description'      => 'Customer deposit cleared',
            ]);
        }

        JournalEntryLine::create([
            'journal_entry_id' => $entry->id,
            'account_id'       => $revenue->id,
            'debit'            => 0,
            'credit'           => $officialTotal,
            'description'      => 'Sales revenue recognized on delivery',
        ]);

        return $entry;
    }

    private function calculateOfficialTotal(Sale $sale): float
    {
        $sale->loadMissing('items');
        $officialSubtotal = $sale->items->sum(
            fn($item) => ($item->unit_price * $item->quantity) - $item->discount
        );
        return round($officialSubtotal - $sale->discount + $sale->tax, 2);
    }
}
