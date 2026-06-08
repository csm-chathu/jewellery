<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\AuditLog;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\Layaway;
use App\Models\LayawayPayment;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\ShopSetting;
use App\Services\SmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LayawayController extends Controller
{
    public function index(Request $request)
    {
        return Layaway::with([
            'customer:id,name,phone',
            'product:id,name,sku',
            'creator:id,name',
            'sale:id,invoice_number',
        ])
            ->when($request->customer_id, fn($q, $v) => $q->where('customer_id', $v))
            ->when($request->status,      fn($q, $v) => $q->where('status', $v))
            ->when($request->from,        fn($q, $v) => $q->where('booking_date', '>=', $v))
            ->when($request->to,          fn($q, $v) => $q->where('booking_date', '<=', $v))
            ->orderByDesc('id')
            ->paginate(20);
    }

    public function show(Layaway $layaway)
    {
        return $layaway->load([
            'customer:id,name,phone,email',
            'product:id,name,sku',
            'payments.creator:id,name',
            'creator:id,name',
            'sale:id,invoice_number',
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'customer_id'      => 'required|exists:customers,id',
            'product_id'       => 'nullable|exists:products,id',
            'item_description' => 'required|string|max:255',
            'total_amount'     => 'required|numeric|min:1',
            'initial_payment'  => 'nullable|numeric|min:0',
            'booking_date'     => 'required|date',
            'expected_by'      => 'nullable|date|after_or_equal:booking_date',
            'notes'            => 'nullable|string|max:500',
            'payment_method'   => 'nullable|in:cash,bank_transfer,cheque,card',
            'send_sms'         => 'nullable|boolean',
        ]);

        $initialPayment = $data['initial_payment'] ?? 0;

        if ($initialPayment > $data['total_amount']) {
            return response()->json(['message' => 'Initial payment cannot exceed total amount.'], 422);
        }

        DB::beginTransaction();
        try {
            $layaway = Layaway::create([
                'customer_id'      => $data['customer_id'],
                'product_id'       => $data['product_id'] ?? null,
                'item_description' => $data['item_description'],
                'total_amount'     => $data['total_amount'],
                'paid_amount'      => 0,
                'balance_amount'   => $data['total_amount'],
                'status'           => 'active',
                'booking_date'     => $data['booking_date'],
                'expected_by'      => $data['expected_by'] ?? null,
                'notes'            => $data['notes'] ?? null,
                'branch_id'        => auth()->user()->branch_id,
                'created_by'       => auth()->id(),
            ]);

            if ($initialPayment > 0) {
                $this->recordPayment($layaway, [
                    'amount'         => $initialPayment,
                    'payment_method' => $data['payment_method'] ?? 'cash',
                    'payment_date'   => $data['booking_date'],
                    'notes'          => 'Initial booking payment',
                    'send_sms'       => $data['send_sms'] ?? false,
                ]);
                $layaway->refresh();
            }

            AuditLog::record('layaway_created', "Layaway {$layaway->reference_number} created for {$layaway->item_description}", $layaway, [], []);

            DB::commit();

            return response()->json(
                $layaway->load(['customer:id,name,phone', 'payments.creator:id,name', 'creator:id,name']),
                201
            );
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to create layaway: ' . $e->getMessage()], 500);
        }
    }

    public function update(Request $request, Layaway $layaway)
    {
        if (in_array($layaway->status, ['completed', 'cancelled'])) {
            return response()->json(['message' => 'Cannot edit a completed or cancelled layaway.'], 422);
        }

        $data = $request->validate([
            'item_description' => 'sometimes|string|max:255',
            'total_amount'     => 'sometimes|numeric|min:1',
            'expected_by'      => 'nullable|date',
            'notes'            => 'nullable|string|max:500',
        ]);

        if (isset($data['total_amount']) && $data['total_amount'] < $layaway->paid_amount) {
            return response()->json(['message' => 'Total amount cannot be less than amount already paid.'], 422);
        }

        if (isset($data['total_amount'])) {
            $data['balance_amount'] = $data['total_amount'] - $layaway->paid_amount;
        }

        $layaway->update($data);

        return response()->json($layaway->load(['customer:id,name,phone', 'payments.creator:id,name']));
    }

    public function pay(Request $request, Layaway $layaway)
    {
        if ($layaway->status === 'completed') {
            return response()->json(['message' => 'Layaway is already fully paid.'], 422);
        }
        if ($layaway->status === 'cancelled') {
            return response()->json(['message' => 'Cannot record payment on a cancelled layaway.'], 422);
        }

        $data = $request->validate([
            'amount'         => 'required|numeric|min:0.01',
            'payment_method' => 'required|in:cash,bank_transfer,cheque,card',
            'payment_date'   => 'required|date',
            'notes'          => 'nullable|string|max:500',
            'send_sms'       => 'nullable|boolean',
        ]);

        if ($data['amount'] > $layaway->balance_amount) {
            return response()->json([
                'message' => 'Payment amount exceeds balance of LKR ' . number_format($layaway->balance_amount, 2),
            ], 422);
        }

        DB::beginTransaction();
        try {
            $payment = $this->recordPayment($layaway, $data);
            $layaway->refresh();

            AuditLog::record(
                'layaway_payment',
                "Payment {$payment->receipt_number} of LKR {$payment->amount} on {$layaway->reference_number}",
                $payment, [], []
            );

            DB::commit();

            return response()->json([
                'payment' => $payment->load('creator:id,name'),
                'layaway' => $layaway->load(['customer:id,name,phone', 'payments.creator:id,name']),
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to record payment: ' . $e->getMessage()], 500);
        }
    }

    public function convertToSale(Request $request, Layaway $layaway)
    {
        if ($layaway->status === 'cancelled') {
            return response()->json(['message' => 'Cannot convert a cancelled layaway.'], 422);
        }

        if ($layaway->sale_id) {
            return response()->json(['message' => 'This layaway has already been converted to sale ' . optional($layaway->sale)->invoice_number . '.'], 422);
        }

        $data = $request->validate([
            'payment_method' => 'required|in:cash,bank_transfer,cheque,card',
            'collected_at'   => 'nullable|date',
            'notes'          => 'nullable|string|max:500',
            'cash_collected' => 'nullable|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            $collectedAt = !empty($data['collected_at']) ? $data['collected_at'] : now()->toDateString();

            // ── Record any remaining balance paid in cash at handover ───────
            $cashCollected = (float) ($data['cash_collected'] ?? 0);
            if ($cashCollected > 0) {
                if ($cashCollected > $layaway->balance_amount) {
                    return response()->json([
                        'message' => 'Cash collected exceeds outstanding balance of LKR ' . number_format($layaway->balance_amount, 2),
                    ], 422);
                }
                $this->recordPayment($layaway, [
                    'amount'         => $cashCollected,
                    'payment_method' => $data['payment_method'],
                    'payment_date'   => $collectedAt,
                    'notes'          => 'Balance collected at handover',
                    'send_sms'       => false,
                ]);
                $layaway->refresh();
            }

            // ── Create Sale record ──────────────────────────────────────────
            // Use paid_amount as the actual sale value (covers partial-pay handover cases)
            $saleAmount = (float) $layaway->paid_amount;
            $discount   = max(0, (float) $layaway->total_amount - $saleAmount);

            $invoiceSeq    = Sale::whereDate('created_at', today())->count() + 1;
            $invoiceNumber = 'INV-' . now()->format('Ymd') . '-' . str_pad($invoiceSeq, 4, '0', STR_PAD_LEFT);

            $sale = Sale::create([
                'branch_id'       => $layaway->branch_id ?? auth()->user()->branch_id,
                'invoice_number'  => $invoiceNumber,
                'customer_id'     => $layaway->customer_id,
                'user_id'         => auth()->id(),
                'subtotal'        => $layaway->total_amount,
                'discount'        => $discount,
                'tax'             => 0,
                'tax_rate'        => 0,
                'total'           => $saleAmount,
                'gold_value_total'      => 0,
                'gemstone_value_total'  => 0,
                'making_charges_total'  => 0,
                'wastage_total'         => 0,
                'payment_method'  => $data['payment_method'],
                'payment_status'  => 'paid',
                'sale_type'       => 'instant',
                'delivery_status' => 'delivered',
                'delivered_at'    => $collectedAt,
                'amount_paid'     => $saleAmount,
                'notes'           => 'Converted from layaway ' . $layaway->reference_number . ($data['notes'] ? '. ' . $data['notes'] : ''),
                'sold_at'         => $collectedAt,
            ]);

            // ── Create SaleItem if product is linked ────────────────────────
            if ($layaway->product_id) {
                $product = Product::find($layaway->product_id);
                if ($product && $product->stock_quantity > 0) {
                    SaleItem::create([
                        'sale_id'        => $sale->id,
                        'product_id'     => $product->id,
                        'quantity'       => 1,
                        'unit_price'     => $layaway->total_amount,
                        'discount'       => $discount,
                        'total'          => $saleAmount,
                        'gold_value'     => 0,
                        'gemstone_value' => 0,
                        'making_charge'  => 0,
                        'wastage_amount' => 0,
                    ]);
                    $product->decrement('stock_quantity', 1);
                }
            }

            // ── Post GL Journal ─────────────────────────────────────────────
            // Layaway payments were: DR Cash/Bank → CR Customer Deposit (2200) per payment
            // Conversion clears: DR Customer Deposit (2200) → CR Sales Revenue (4000)
            $depositAccount = Account::where('code', '2200')->first();
            $revenueAccount = Account::where('code', '4000')->first();

            if (!$depositAccount || !$revenueAccount) {
                throw new \RuntimeException('Required GL accounts (2200 Customer Deposit, 4000 Revenue) not found.');
            }

            $entrySeq    = JournalEntry::whereDate('created_at', today())->withTrashed()->count() + 1;
            $entryNumber = 'JE-' . date('Ymd') . '-' . str_pad($entrySeq, 4, '0', STR_PAD_LEFT);

            $entry = JournalEntry::create([
                'entry_number'   => $entryNumber,
                'entry_date'     => $collectedAt,
                'description'    => "Layaway converted to sale {$invoiceNumber} — {$layaway->reference_number}",
                'reference_type' => 'Sale',
                'reference_id'   => $sale->id,
                'branch_id'      => $sale->branch_id,
                'created_by'     => auth()->id(),
                'status'         => 'posted',
            ]);

            // DR Customer Deposit (clear liability — only what was actually deposited)
            JournalEntryLine::create([
                'journal_entry_id' => $entry->id,
                'account_id'       => $depositAccount->id,
                'debit'            => $saleAmount,
                'credit'           => 0,
                'description'      => "Layaway deposit cleared — {$layaway->reference_number}",
            ]);

            // CR Sales Revenue
            JournalEntryLine::create([
                'journal_entry_id' => $entry->id,
                'account_id'       => $revenueAccount->id,
                'debit'            => 0,
                'credit'           => $saleAmount,
                'description'      => "Sales revenue recognised — {$invoiceNumber}",
            ]);

            $sale->update(['journal_entry_id' => $entry->id]);

            // ── Mark layaway as collected ───────────────────────────────────
            $layaway->update([
                'sale_id'      => $sale->id,
                'collected_at' => $collectedAt,
            ]);

            AuditLog::record(
                'layaway_converted',
                "Layaway {$layaway->reference_number} converted to sale {$invoiceNumber}",
                $sale, [], []
            );

            DB::commit();

            return response()->json([
                'sale'    => $sale->load(['items.product', 'customer:id,name,phone,email', 'journalEntry']),
                'layaway' => $layaway->load(['customer:id,name,phone', 'payments', 'sale:id,invoice_number']),
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to convert layaway: ' . $e->getMessage()], 500);
        }
    }

    public function cancel(Request $request, Layaway $layaway)
    {
        if ($layaway->status === 'completed') {
            return response()->json(['message' => 'Cannot cancel a completed layaway.'], 422);
        }
        if ($layaway->status === 'cancelled') {
            return response()->json(['message' => 'Layaway is already cancelled.'], 422);
        }

        $data = $request->validate([
            'refund_type'         => 'required|in:full,partial,forfeit',
            'cancellation_fee'    => 'nullable|numeric|min:0',
            'cancellation_reason' => 'nullable|string|max:500',
            'refund_method'       => 'nullable|in:cash,bank_transfer',
            'cancelled_at'        => 'nullable|date',
        ]);

        $paidAmount = (float) $layaway->paid_amount;

        switch ($data['refund_type']) {
            case 'full':
                $cancellationFee = 0.0;
                $refundAmount    = $paidAmount;
                break;
            case 'partial':
                $cancellationFee = min((float) ($data['cancellation_fee'] ?? 0), $paidAmount);
                $refundAmount    = max(0.0, $paidAmount - $cancellationFee);
                break;
            case 'forfeit':
                $cancellationFee = $paidAmount;
                $refundAmount    = 0.0;
                break;
        }

        DB::beginTransaction();
        try {
            $cancelledAt = $data['cancelled_at'] ?? now()->toDateString();
            $journalId   = null;

            if ($paidAmount > 0) {
                $journalId = $this->postCancellationJournal(
                    $layaway, $paidAmount, $refundAmount, $cancellationFee,
                    $data['refund_method'] ?? 'cash', $cancelledAt
                );
            }

            $layaway->update([
                'status'                  => 'cancelled',
                'cancelled_at'            => $cancelledAt,
                'cancellation_reason'     => $data['cancellation_reason'] ?? null,
                'refund_type'             => $data['refund_type'],
                'cancellation_fee'        => $cancellationFee,
                'refund_amount'           => $refundAmount,
                'refund_method'           => $data['refund_method'] ?? null,
                'cancellation_journal_id' => $journalId,
            ]);

            AuditLog::record(
                'layaway_cancelled',
                "Layaway {$layaway->reference_number} cancelled. Type: {$data['refund_type']}, Refund: LKR {$refundAmount}, Fee: LKR {$cancellationFee}",
                $layaway, [], []
            );

            DB::commit();

            return response()->json($layaway->load(['customer:id,name,phone', 'payments.creator:id,name']));
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to cancel: ' . $e->getMessage()], 500);
        }
    }

    private function postCancellationJournal(
        Layaway $layaway, float $paidAmount, float $refundAmount,
        float $fee, string $refundMethod, string $date
    ): ?int {
        $depositAccount    = Account::where('code', '2200')->first();
        $cashAccount       = $refundMethod === 'bank_transfer'
            ? Account::where('code', '1010')->first()
            : Account::where('code', '1000')->first();
        $forfeitureAccount = Account::where('code', '4050')->first();

        if (!$depositAccount) {
            return null;
        }

        $entrySeq    = JournalEntry::whereDate('created_at', today())->withTrashed()->count() + 1;
        $entryNumber = 'JE-' . date('Ymd') . '-' . str_pad($entrySeq, 4, '0', STR_PAD_LEFT);

        $entry = JournalEntry::create([
            'entry_number'   => $entryNumber,
            'entry_date'     => $date,
            'description'    => "Layaway cancelled — {$layaway->reference_number}",
            'reference_type' => 'Layaway',
            'reference_id'   => $layaway->id,
            'branch_id'      => $layaway->branch_id,
            'created_by'     => auth()->id(),
            'status'         => 'posted',
        ]);

        // DR Customer Deposit (2200) — clear the liability
        JournalEntryLine::create([
            'journal_entry_id' => $entry->id,
            'account_id'       => $depositAccount->id,
            'debit'            => $paidAmount,
            'credit'           => 0,
            'description'      => "Deposit reversed on cancellation — {$layaway->reference_number}",
        ]);

        // CR Cash/Bank — refund to customer
        if ($refundAmount > 0 && $cashAccount) {
            JournalEntryLine::create([
                'journal_entry_id' => $entry->id,
                'account_id'       => $cashAccount->id,
                'debit'            => 0,
                'credit'           => $refundAmount,
                'description'      => "Refund paid to customer — {$layaway->reference_number}",
            ]);
        }

        // CR Cancellation Fee Income (4050) — forfeited portion
        if ($fee > 0 && $forfeitureAccount) {
            JournalEntryLine::create([
                'journal_entry_id' => $entry->id,
                'account_id'       => $forfeitureAccount->id,
                'debit'            => 0,
                'credit'           => $fee,
                'description'      => "Cancellation fee income — {$layaway->reference_number}",
            ]);
        }

        return $entry->id;
    }

    public function destroy(Layaway $layaway)
    {
        if ($layaway->paid_amount > 0) {
            return response()->json(['message' => 'Cannot delete a layaway with payments recorded.'], 422);
        }

        AuditLog::record('layaway_deleted', "Layaway {$layaway->reference_number} deleted", $layaway, [], []);
        $layaway->delete();

        return response()->json(['message' => 'Layaway deleted.']);
    }

    // ── Private ─────────────────────────────────────────────────────────────

    private function recordPayment(Layaway $layaway, array $data): LayawayPayment
    {
        $payment = LayawayPayment::create([
            'layaway_id'     => $layaway->id,
            'amount'         => $data['amount'],
            'payment_method' => $data['payment_method'] ?? 'cash',
            'payment_date'   => $data['payment_date'],
            'notes'          => $data['notes'] ?? null,
            'sms_sent'       => false,
            'created_by'     => auth()->id(),
        ]);

        $newPaid    = $layaway->paid_amount + $data['amount'];
        $newBalance = $layaway->total_amount - $newPaid;
        $newStatus  = $newBalance <= 0 ? 'completed' : $layaway->status;

        $layaway->update([
            'paid_amount'    => $newPaid,
            'balance_amount' => max(0, $newBalance),
            'status'         => $newStatus,
        ]);

        // ── GL: DR Cash/Bank → CR Customer Deposit (2200) ──────────────────
        $this->postPaymentJournal($payment, $layaway);

        if (!empty($data['send_sms'])) {
            $smsSent = $this->sendPaymentSms($layaway, $payment);
            if ($smsSent) {
                $payment->update(['sms_sent' => true]);
            }
        }

        return $payment;
    }

    private function postPaymentJournal(LayawayPayment $payment, Layaway $layaway): void
    {
        $depositAccount = Account::where('code', '2200')->first();
        $cashAccount    = $this->paymentAccountByMethod($payment->payment_method);

        if (!$depositAccount || !$cashAccount) {
            // GL accounts may not be set up — skip silently rather than fail the payment
            return;
        }

        $entrySeq    = JournalEntry::whereDate('created_at', today())->withTrashed()->count() + 1;
        $entryNumber = 'JE-' . date('Ymd') . '-' . str_pad($entrySeq, 4, '0', STR_PAD_LEFT);
        $desc        = "Layaway payment {$payment->receipt_number} — {$layaway->reference_number}";

        $entry = JournalEntry::create([
            'entry_number'   => $entryNumber,
            'entry_date'     => $payment->payment_date,
            'description'    => $desc,
            'reference_type' => 'LayawayPayment',
            'reference_id'   => $payment->id,
            'branch_id'      => $layaway->branch_id,
            'created_by'     => auth()->id(),
            'status'         => 'posted',
        ]);

        JournalEntryLine::create([
            'journal_entry_id' => $entry->id,
            'account_id'       => $cashAccount->id,
            'debit'            => $payment->amount,
            'credit'           => 0,
            'description'      => $desc,
        ]);

        JournalEntryLine::create([
            'journal_entry_id' => $entry->id,
            'account_id'       => $depositAccount->id,
            'debit'            => 0,
            'credit'           => $payment->amount,
            'description'      => $desc,
        ]);
    }

    private function paymentAccountByMethod(string $method): ?Account
    {
        return match ($method) {
            'cash'  => Account::where('code', '1000')->first(),
            default => Account::where('code', '1010')->first(),
        };
    }

    private function sendPaymentSms(Layaway $layaway, LayawayPayment $payment): bool
    {
        $customer = $layaway->customer ?? $layaway->load('customer')->customer;

        if (!$customer->phone) {
            return false;
        }

        $shopName = optional(ShopSetting::first())->shop_name ?? 'Our Shop';
        $balance  = $layaway->balance_amount;

        $msg = "Dear {$customer->name}, your payment of LKR " . number_format($payment->amount, 2) .
               " for {$layaway->item_description} has been received. " .
               "Balance: LKR " . number_format($balance, 2) . ". " .
               "Ref: {$layaway->reference_number}. Thank you! - {$shopName}";

        $smsService = app(SmsService::class);
        $result     = $smsService->sendSingle($customer->phone, $msg);

        return $result['success'] ?? false;
    }
}
