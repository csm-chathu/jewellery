<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\AuditLog;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\Sale;
use App\Models\SaleReturn;
use App\Models\SaleReturnItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SaleReturnController extends Controller
{
    /** List all returns for a sale */
    public function index(Sale $sale)
    {
        $this->authorizeBranch($sale->branch_id);
        return response()->json($sale->returns()->with(['user:id,name', 'items.product:id,name,sku'])->latest()->get());
    }

    /** Process a return against a sale */
    public function store(Request $request, Sale $sale)
    {
        $this->authorizeBranch($sale->branch_id);

        if (!in_array($sale->payment_status, ['paid', 'partial', 'refunded'])) {
            return response()->json(['message' => 'Only paid or partially paid sales can be returned.'], 422);
        }

        $data = $request->validate([
            'reason'        => 'required|string|max:500',
            'refund_method' => 'required|in:cash,card,bank_transfer,store_credit,none',
            'notes'         => 'nullable|string|max:1000',
            'items'         => 'required|array|min:1',
            'items.*.sale_item_id' => 'required|exists:sale_items,id',
            'items.*.quantity'     => 'required|integer|min:1',
        ]);

        // Load items with existing return totals
        $saleItems = $sale->items()->with('product')->get()->keyBy('id');

        // Calculate already-returned quantities per sale_item
        $alreadyReturned = SaleReturnItem::whereIn('sale_item_id', $saleItems->keys())
            ->selectRaw('sale_item_id, SUM(quantity) as total_returned')
            ->groupBy('sale_item_id')
            ->pluck('total_returned', 'sale_item_id');

        DB::beginTransaction();
        try {
            $refundAmount = 0;
            $returnItems  = [];

            foreach ($data['items'] as $row) {
                $saleItem = $saleItems->get($row['sale_item_id']);

                if (!$saleItem || $saleItem->sale_id !== $sale->id) {
                    throw new \Exception("Item {$row['sale_item_id']} does not belong to this sale.");
                }

                $prevReturned  = (int) ($alreadyReturned[$saleItem->id] ?? 0);
                $returnableQty = $saleItem->quantity - $prevReturned;

                if ($row['quantity'] > $returnableQty) {
                    throw new \Exception("Cannot return {$row['quantity']} of '{$saleItem->product->name}'. Only {$returnableQty} returnable.");
                }

                $lineTotal     = round(($saleItem->total / $saleItem->quantity) * $row['quantity'], 2);
                $refundAmount += $lineTotal;

                $returnItems[] = [
                    'sale_item_id' => $saleItem->id,
                    'product'      => $saleItem->product,
                    'quantity'     => $row['quantity'],
                    'unit_price'   => round($saleItem->total / $saleItem->quantity, 2),
                    'total'        => $lineTotal,
                ];
            }

            $returnNumber = 'RET-' . now()->format('Ymd') . '-' . str_pad(SaleReturn::whereDate('created_at', today())->count() + 1, 4, '0', STR_PAD_LEFT);

            $saleReturn = SaleReturn::create([
                'return_number' => $returnNumber,
                'sale_id'       => $sale->id,
                'user_id'       => $request->user()->id,
                'branch_id'     => $sale->branch_id,
                'reason'        => $data['reason'],
                'refund_method' => $data['refund_method'],
                'refund_amount' => $refundAmount,
                'notes'         => $data['notes'] ?? null,
                'returned_at'   => now(),
            ]);

            foreach ($returnItems as $ri) {
                SaleReturnItem::create([
                    'sale_return_id' => $saleReturn->id,
                    'sale_item_id'   => $ri['sale_item_id'],
                    'product_id'     => $ri['product']->id,
                    'quantity'       => $ri['quantity'],
                    'unit_price'     => $ri['unit_price'],
                    'total'          => $ri['total'],
                ]);

                // Restore stock
                $ri['product']->increment('stock_quantity', $ri['quantity']);
            }

            // Post reversal journal entry
            if ($refundAmount > 0 && $data['refund_method'] !== 'none') {
                $this->postReturnJournal($sale, $saleReturn, $refundAmount, $data['refund_method']);
            }

            // Update sale payment_status
            $totalReturned = SaleReturnItem::whereIn('sale_item_id', $saleItems->keys())
                ->sum('quantity');
            $totalSold = $saleItems->sum('quantity');

            if ($totalReturned >= $totalSold) {
                $sale->update(['payment_status' => 'refunded']);
            }

            AuditLog::record('sale_return_created', "Return {$returnNumber} for sale {$sale->invoice_number} — LKR {$refundAmount}", $saleReturn);

            DB::commit();
            return response()->json($saleReturn->load(['items.product:id,name,sku', 'user:id,name']), 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    private function postReturnJournal(Sale $sale, SaleReturn $saleReturn, float $amount, string $refundMethod): void
    {
        $revenue     = Account::where('code', '4000')->first();
        $cashAccount = $refundMethod === 'cash'
            ? Account::where('code', '1000')->first()
            : Account::where('code', '1010')->first();

        if (!$revenue || !$cashAccount) return; // silently skip if accounts not configured

        $entry = JournalEntry::create([
            'entry_number'   => 'JE-' . date('Ymd') . '-' . str_pad(JournalEntry::whereDate('created_at', today())->withTrashed()->count() + 1, 4, '0', STR_PAD_LEFT),
            'entry_date'     => now(),
            'description'    => "Sales return {$saleReturn->return_number} for invoice {$sale->invoice_number}",
            'reference_type' => 'SaleReturn',
            'reference_id'   => $saleReturn->id,
            'branch_id'      => $sale->branch_id,
            'created_by'     => auth()->id(),
            'status'         => 'posted',
        ]);

        // DR Revenue (reverse the income)
        JournalEntryLine::create([
            'journal_entry_id' => $entry->id,
            'account_id'       => $revenue->id,
            'debit'            => $amount,
            'credit'           => 0,
            'description'      => 'Sales return — revenue reversal',
        ]);

        // CR Cash/Bank (refund paid out)
        JournalEntryLine::create([
            'journal_entry_id' => $entry->id,
            'account_id'       => $cashAccount->id,
            'debit'            => 0,
            'credit'           => $amount,
            'description'      => 'Refund to customer',
        ]);
    }

    private function authorizeBranch(?int $branchId): void
    {
        $user = request()->user();
        if (!$user->isAdmin() && $user->branch_id !== $branchId) {
            abort(403, 'Forbidden for this branch.');
        }
    }
}
