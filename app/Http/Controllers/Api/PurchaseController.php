<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\AuditLog;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseController extends Controller
{
    public function index()
    {
        $user = request()->user();
        $purchases = Purchase::with(['supplier:id,name', 'user:id,name', 'journalEntry:id,entry_number'])
            ->when(!$user->isAdmin(), fn($q) => $q->where('branch_id', $user->branch_id))
            ->when(request('search'), fn($q, $s) => $q->where('purchase_number', 'like', "%$s%"))
            ->when(request('supplier_id'), fn($q, $s) => $q->where('supplier_id', $s))
            ->latest('purchased_at')
            ->paginate(request('per_page', 20));
        return response()->json($purchases);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'items'       => 'required|array|min:1',
            'items.*.product_id'         => 'nullable|exists:products,id',
            'items.*.new_product.name'             => 'nullable|required_without:items.*.product_id|string|max:200',
            'items.*.new_product.category_id'      => 'nullable|required_with:items.*.new_product.name|exists:categories,id',
            'items.*.new_product.material'         => 'nullable|string|max:50',
            'items.*.new_product.karat'            => 'nullable|string|max:10',
            'items.*.new_product.weight'           => 'nullable|numeric|min:0',
            'items.*.new_product.image_url'        => 'nullable|url|max:1000',
            'items.*.new_product.image_public_id'  => 'nullable|string|max:255',
            'items.*.quantity'      => 'required|integer|min:1',
            'items.*.unit_cost'     => 'required|numeric|min:0',
            'items.*.selling_price' => 'nullable|numeric|min:0',
            'tax'         => 'nullable|numeric|min:0',
            'status'      => 'required|in:pending,received,partial,cancelled',
            'payment_method'   => 'nullable|in:cash,bank_transfer,cheque,credit',
            'cheque_number'    => 'nullable|required_if:payment_method,cheque|string|max:50',
            'cheque_date'      => 'nullable|required_if:payment_method,cheque|date',
            'cheque_bank_name' => 'nullable|required_if:payment_method,cheque|string|max:100',
            'notes'       => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $subtotal = collect($data['items'])->sum(fn($i) => $i['unit_cost'] * $i['quantity']);
            $tax      = $data['tax'] ?? 0;

            $purchase = Purchase::create([
                'branch_id'       => $request->user()->branch_id,
                'purchase_number' => 'PO-' . now()->format('Ymd') . '-' . str_pad(Purchase::whereDate('created_at', today())->count() + 1, 4, '0', STR_PAD_LEFT),
                'supplier_id'     => $data['supplier_id'],
                'user_id'         => $request->user()->id,
                'subtotal'        => $subtotal,
                'tax'             => $tax,
                'total'           => $subtotal + $tax,
                'status'          => $data['status'],
                'payment_method'  => $data['payment_method'] ?? 'cash',
                'cheque_number'   => $data['cheque_number'] ?? null,
                'cheque_date'     => $data['cheque_date'] ?? null,
                'cheque_bank_name'=> $data['cheque_bank_name'] ?? null,
                'notes'           => $data['notes'] ?? null,
            ]);

            foreach ($data['items'] as $item) {
                // Create product on-the-fly if no existing product selected
                if (empty($item['product_id'])) {
                    $np   = $item['new_product'];
                    $last = Product::withTrashed()->max('id') ?? 0;
                    $product = Product::create([
                        'branch_id'       => $request->user()->branch_id,
                        'name'            => $np['name'],
                        'category_id'     => $np['category_id'],
                        'material'        => $np['material'] ?? null,
                        'karat'           => $np['karat'] ?? null,
                        'weight'          => $np['weight'] ?? null,
                        'supplier_id'     => $data['supplier_id'],
                        'sku'             => str_pad($last + 1, 6, '0', STR_PAD_LEFT),
                        'purchase_price'  => $item['unit_cost'],
                        'selling_price'   => $item['selling_price'] ?? 0,
                        'stock_quantity'  => 0,
                        'min_stock_level' => 1,
                        'is_active'       => true,
                        'image'           => $np['image_url'] ?? null,
                        'image_public_id' => $np['image_public_id'] ?? null,
                    ]);
                } else {
                    $product = Product::findOrFail($item['product_id']);
                    if (!$request->user()->isAdmin() && $product->branch_id !== $request->user()->branch_id) {
                        throw new \Exception("Product not available for your branch: {$product->name}");
                    }
                }

                PurchaseItem::create([
                    'purchase_id'   => $purchase->id,
                    'product_id'    => $product->id,
                    'quantity'      => $item['quantity'],
                    'unit_cost'     => $item['unit_cost'],
                    'selling_price' => $item['selling_price'] ?? 0,
                    'total'         => $item['unit_cost'] * $item['quantity'],
                ]);
                if ($data['status'] === 'received') {
                    $product->increment('stock_quantity', $item['quantity']);
                    $updates = ['purchase_price' => $item['unit_cost']];
                    if (!empty($item['selling_price'])) {
                        $updates['selling_price'] = $item['selling_price'];
                    }
                    $product->update($updates);
                }
            }

            if ($purchase->status === 'received') {
                $entry = $this->postPurchaseJournal($purchase);
                $purchase->update(['journal_entry_id' => $entry->id]);
            }

            AuditLog::record('purchase_created', "Purchase {$purchase->purchase_number} created", $purchase);

            DB::commit();
            return response()->json($purchase->load(['items.product', 'supplier', 'user']), 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function show(Purchase $purchase)
    {
        $this->authorizeBranch($purchase->branch_id);
        return response()->json($purchase->load(['items.product', 'supplier', 'user']));
    }

    public function receive(Request $request, Purchase $purchase)
    {
        $this->authorizeBranch($purchase->branch_id);

        if ($purchase->status === 'received') {
            return response()->json(['message' => 'Purchase already received.'], 422);
        }

        $data = $request->validate([
            'items'                 => 'required|array|min:1',
            'items.*.id'            => 'required|exists:purchase_items,id',
            'items.*.quantity'      => 'required|integer|min:0',
            'items.*.unit_cost'     => 'required|numeric|min:0',
            'items.*.selling_price' => 'nullable|numeric|min:0',
            'notes'                 => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $subtotal = 0;
            foreach ($data['items'] as $row) {
                $item    = PurchaseItem::findOrFail($row['id']);
                $product = Product::findOrFail($item->product_id);

                $item->update([
                    'quantity'      => $row['quantity'],
                    'unit_cost'     => $row['unit_cost'],
                    'selling_price' => $row['selling_price'] ?? $item->selling_price,
                    'total'         => $row['unit_cost'] * $row['quantity'],
                ]);

                $subtotal += $item->total;

                if ($row['quantity'] > 0) {
                    $product->increment('stock_quantity', $row['quantity']);
                    $updates = ['purchase_price' => $row['unit_cost']];
                    if (!empty($row['selling_price'])) {
                        $updates['selling_price'] = $row['selling_price'];
                    }
                    $product->update($updates);
                }
            }

            $purchase->update([
                'status'   => 'received',
                'subtotal' => $subtotal,
                'total'    => $subtotal + $purchase->tax,
                'notes'    => $data['notes'] ?? $purchase->notes,
            ]);

            $entry = $this->postPurchaseJournal($purchase->fresh());
            $purchase->update(['journal_entry_id' => $entry->id]);

            AuditLog::record('purchase_received', "GRN received for {$purchase->purchase_number}", $purchase);

            DB::commit();
            return response()->json($purchase->load(['items.product', 'supplier', 'user']));
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function destroy(Purchase $purchase)
    {
        $this->authorizeBranch($purchase->branch_id);
        $purchase->delete();
        return response()->json(['message' => 'Purchase deleted']);
    }

    private function authorizeBranch(?int $branchId): void
    {
        $user = request()->user();
        if (!$user->isAdmin() && $user->branch_id !== $branchId) {
            abort(403, 'Forbidden for this branch.');
        }
    }

    private function postPurchaseJournal(Purchase $purchase): JournalEntry
    {
        $inventory = Account::where('code', '1200')->first();
        $ap = Account::where('code', '2000')->first();
        $paymentAccount = $this->paymentAccountByMethod($purchase->payment_method);

        if (!$inventory) {
            throw new \Exception('Inventory account (1200) not found.');
        }

        if ($purchase->payment_method === 'credit' && !$ap) {
            throw new \Exception('Accounts payable account (2000) not found for credit purchase.');
        }

        if ($purchase->payment_method !== 'credit' && $purchase->payment_method !== 'cheque' && !$paymentAccount) {
            throw new \Exception('Payment account not found for purchase payment method.');
        }

        $entry = JournalEntry::create([
            'entry_number'   => $this->nextEntryNumber(),
            'entry_date'     => $purchase->purchased_at ?? now(),
            'description'    => "Purchase {$purchase->purchase_number}",
            'reference_type' => 'Purchase',
            'reference_id'   => $purchase->id,
            'branch_id'      => $purchase->branch_id,
            'created_by'     => auth()->id(),
            'status'         => 'posted',
        ]);

        JournalEntryLine::create([
            'journal_entry_id' => $entry->id,
            'account_id'       => $inventory->id,
            'debit'            => $purchase->total,
            'credit'           => 0,
            'description'      => 'Inventory purchased',
        ]);

        JournalEntryLine::create([
            'journal_entry_id' => $entry->id,
            'account_id'       => $purchase->payment_method === 'credit' ? $ap->id : $paymentAccount->id,
            'debit'            => 0,
            'credit'           => $purchase->total,
            'description'      => $purchase->payment_method === 'credit' ? 'Supplier payable recorded' : 'Purchase paid',
        ]);

        return $entry;
    }

    public function settleChequePurchase(Request $request, Purchase $purchase)
    {
        $this->authorizeBranch($purchase->branch_id);

        if ($purchase->payment_method !== 'cheque') {
            return response()->json(['message' => 'This purchase is not a cheque payment.'], 422);
        }
        if ($purchase->cheque_settled_at) {
            return response()->json(['message' => 'Cheque already settled.'], 422);
        }

        $data = $request->validate([
            'settled_date' => 'nullable|date',
            'notes'        => 'nullable|string',
            'account_id'   => 'nullable|exists:accounts,id',
        ]);

        DB::beginTransaction();
        try {
            $chequesPayable = Account::where('code', '2050')->first();

            // Use the chosen account, or fall back to Bank Account (1010)
            $bank = isset($data['account_id'])
                ? Account::findOrFail($data['account_id'])
                : Account::where('code', '1010')->first();

            if (!$chequesPayable || !$bank) {
                throw new \Exception('Cheques Payable (2050) or settlement account not found.');
            }

            $settledAt = isset($data['settled_date']) ? $data['settled_date'] : now();

            $entry = JournalEntry::create([
                'entry_number'   => $this->nextEntryNumber(),
                'entry_date'     => $settledAt,
                'description'    => "Cheque settled – {$purchase->purchase_number} (#{$purchase->cheque_number}, {$purchase->cheque_bank_name})",
                'reference_type' => 'Purchase',
                'reference_id'   => $purchase->id,
                'branch_id'      => $purchase->branch_id,
                'created_by'     => auth()->id(),
                'status'         => 'posted',
            ]);

            JournalEntryLine::create([
                'journal_entry_id' => $entry->id,
                'account_id'       => $chequesPayable->id,
                'debit'            => $purchase->total,
                'credit'           => 0,
                'description'      => "Cheque payable cleared – {$purchase->purchase_number}",
            ]);

            JournalEntryLine::create([
                'journal_entry_id' => $entry->id,
                'account_id'       => $bank->id,
                'debit'            => 0,
                'credit'           => $purchase->total,
                'description'      => "{$bank->name} payment – cheque #{$purchase->cheque_number}",
            ]);

            $purchase->update([
                'cheque_settled_at'    => $settledAt,
                'settlement_journal_id'=> $entry->id,
            ]);

            AuditLog::record('cheque_settled', "Cheque settled for {$purchase->purchase_number}", $purchase);

            DB::commit();
            return response()->json($purchase->fresh());
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    private function paymentAccountByMethod(string $method): ?Account
    {
        if ($method === 'cash') {
            return Account::where('code', '1000')->first();
        }

        if ($method === 'bank_transfer') {
            return Account::where('code', '1010')->first();
        }

        // Cheques go to Cheques Payable (2050); settled separately via settleChequePurchase
        if ($method === 'cheque') {
            return Account::where('code', '2050')->first();
        }

        return null;
    }

    private function nextEntryNumber(): string
    {
        $seq = JournalEntry::whereDate('created_at', today())->withTrashed()->count() + 1;
        return 'JE-' . date('Ymd') . '-' . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }
}
