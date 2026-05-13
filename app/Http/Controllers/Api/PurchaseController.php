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
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity'   => 'required|integer|min:1',
            'items.*.unit_cost'  => 'required|numeric|min:0',
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
                $product = Product::findOrFail($item['product_id']);
                if (!$request->user()->isAdmin() && $product->branch_id !== $request->user()->branch_id) {
                    throw new \Exception("Product is not available for your branch: {$product->name}");
                }

                PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'product_id'  => $item['product_id'],
                    'quantity'    => $item['quantity'],
                    'unit_cost'   => $item['unit_cost'],
                    'total'       => $item['unit_cost'] * $item['quantity'],
                ]);
                if ($data['status'] === 'received') {
                    $product->increment('stock_quantity', $item['quantity']);
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

        if ($purchase->payment_method !== 'credit' && !$paymentAccount) {
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

    private function paymentAccountByMethod(string $method): ?Account
    {
        if ($method === 'cash') {
            return Account::where('code', '1000')->first();
        }

        if (in_array($method, ['bank_transfer', 'cheque'])) {
            return Account::where('code', '1010')->first();
        }

        return null;
    }

    private function nextEntryNumber(): string
    {
        $seq = JournalEntry::whereDate('created_at', today())->withTrashed()->count() + 1;
        return 'JE-' . date('Ymd') . '-' . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }
}
