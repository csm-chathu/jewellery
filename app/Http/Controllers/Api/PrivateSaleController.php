<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InformalGoldPurchase;
use App\Models\PrivateCashAdjustment;
use App\Models\PrivateExpense;
use App\Models\PrivateSale;
use Illuminate\Http\Request;

class PrivateSaleController extends Controller
{
    private function authorise(Request $request): void
    {
        if (($request->user()->role ?? '') !== 'gold_buyer') {
            abort(403, 'Access restricted.');
        }
    }

    public function index(Request $request)
    {
        $this->authorise($request);
        $user = $request->user();

        $rows = PrivateSale::with('recorder:id,name')
            ->when(!$user->isAdmin(), fn($q) => $q->where('branch_id', $user->branch_id))
            ->when($request->search, fn($q, $s) =>
                $q->where(function ($inner) use ($s) {
                    $inner->where('reference_number', 'like', "%$s%")
                          ->orWhere('description', 'like', "%$s%")
                          ->orWhere('buyer_name', 'like', "%$s%");
                })
            )
            ->when($request->date_from, fn($q, $d) => $q->whereDate('sale_date', '>=', $d))
            ->when($request->date_to,   fn($q, $d) => $q->whereDate('sale_date', '<=', $d))
            ->latest('sale_date')
            ->paginate($request->per_page ?? 50);

        return response()->json($rows);
    }

    public function store(Request $request)
    {
        $this->authorise($request);
        $data = $request->validate([
            'sale_date'      => 'required|date',
            'buyer_name'     => 'nullable|string|max:255',
            'description'    => 'nullable|string|max:500',
            'item_type'      => 'required|in:jewelry,coin,bar,scrap,other',
            'gross_weight'   => 'required|numeric|min:0',
            'net_weight'     => 'required|numeric|min:0',
            'declared_karat' => 'required|string|max:10',
            'rate_per_gram'  => 'required|numeric|min:0',
            'total_amount'   => 'required|numeric|min:0',
            'payment_method' => 'required|in:cash,bank_transfer',
            'notes'          => 'nullable|string',
        ]);

        $user = $request->user();
        $sale = PrivateSale::create([
            ...$data,
            'recorded_by' => $user->id,
            'branch_id'   => $user->branch_id,
        ]);

        return response()->json($sale->load('recorder:id,name'), 201);
    }

    public function update(Request $request, PrivateSale $privateSale)
    {
        $this->authorise($request);
        $data = $request->validate([
            'sale_date'      => 'sometimes|date',
            'buyer_name'     => 'nullable|string|max:255',
            'description'    => 'nullable|string|max:500',
            'item_type'      => 'sometimes|in:jewelry,coin,bar,scrap,other',
            'gross_weight'   => 'sometimes|numeric|min:0',
            'net_weight'     => 'sometimes|numeric|min:0',
            'declared_karat' => 'sometimes|string|max:10',
            'rate_per_gram'  => 'sometimes|numeric|min:0',
            'total_amount'   => 'sometimes|numeric|min:0',
            'payment_method' => 'sometimes|in:cash,bank_transfer',
            'notes'          => 'nullable|string',
        ]);

        $privateSale->update($data);

        return response()->json($privateSale->load('recorder:id,name'));
    }

    public function destroy(Request $request, PrivateSale $privateSale)
    {
        $this->authorise($request);
        $privateSale->delete();
        return response()->json(['message' => 'Record deleted']);
    }

    /** Combined cashbook: purchases (cash out) + sales (cash in), sorted by date */
    public function cashbook(Request $request)
    {
        $this->authorise($request);
        $user     = $request->user();
        $from     = $request->date_from;
        $to       = $request->date_to;

        $purchases = InformalGoldPurchase::query()
            ->when(!$user->isAdmin(), fn($q) => $q->where('branch_id', $user->branch_id))
            ->when($from, fn($q, $d) => $q->whereDate('purchase_date', '>=', $d))
            ->when($to,   fn($q, $d) => $q->whereDate('purchase_date', '<=', $d))
            ->get(['id', 'reference_number', 'purchase_date as entry_date', 'description',
                   'declared_karat', 'net_weight', 'final_price as amount', 'payment_method'])
            ->map(fn($r) => [...$r->toArray(), 'kind' => 'purchase']);

        $sales = PrivateSale::query()
            ->when(!$user->isAdmin(), fn($q) => $q->where('branch_id', $user->branch_id))
            ->when($from, fn($q, $d) => $q->whereDate('sale_date', '>=', $d))
            ->when($to,   fn($q, $d) => $q->whereDate('sale_date', '<=', $d))
            ->get(['id', 'reference_number', 'sale_date as entry_date', 'description',
                   'buyer_name', 'declared_karat', 'net_weight', 'total_amount as amount', 'payment_method'])
            ->map(fn($r) => [...$r->toArray(), 'kind' => 'sale']);

        $expenses = PrivateExpense::query()
            ->when(!$user->isAdmin(), fn($q) => $q->where('branch_id', $user->branch_id))
            ->when($from, fn($q, $d) => $q->whereDate('expense_date', '>=', $d))
            ->when($to,   fn($q, $d) => $q->whereDate('expense_date', '<=', $d))
            ->get(['id', 'reference_number', 'expense_date as entry_date', 'category',
                   'description', 'amount', 'payment_method'])
            ->map(fn($r) => [...$r->toArray(), 'kind' => 'expense']);

        $adjustments = PrivateCashAdjustment::query()
            ->when(!$user->isAdmin(), fn($q) => $q->where('branch_id', $user->branch_id))
            ->when($from, fn($q, $d) => $q->whereDate('adjustment_date', '>=', $d))
            ->when($to,   fn($q, $d) => $q->whereDate('adjustment_date', '<=', $d))
            ->get(['id', 'reference_number', 'adjustment_date as entry_date',
                   'description', 'type', 'amount', 'payment_method'])
            ->map(fn($r) => [...$r->toArray(), 'kind' => 'adjustment']);

        $entries = $purchases->concat($sales)->concat($expenses)->concat($adjustments)
            ->sortBy('entry_date')
            ->values();

        // sales + adj(add) = cash in (+); purchases + expenses + adj(withdraw) = cash out (-)
        $balance = 0;
        $rows = $entries->map(function ($e) use (&$balance) {
            if ($e['kind'] === 'sale' || ($e['kind'] === 'adjustment' && $e['type'] === 'add')) {
                $balance += $e['amount'];
                return [...$e, 'cash_in' => $e['amount'], 'cash_out' => 0, 'balance' => $balance];
            } else {
                $balance -= $e['amount'];
                return [...$e, 'cash_in' => 0, 'cash_out' => $e['amount'], 'balance' => $balance];
            }
        });

        $adjIn  = $adjustments->where('type', 'add')->sum('amount');
        $adjOut = $adjustments->where('type', 'withdraw')->sum('amount');
        $totalIn  = $sales->sum('amount') + $adjIn;
        $totalOut = $purchases->sum('amount') + $expenses->sum('amount') + $adjOut;

        return response()->json([
            'entries'          => $rows,
            'total_in'         => $totalIn,
            'total_out'        => $totalOut,
            'total_expenses'   => $expenses->sum('amount'),
            'balance'          => $totalIn - $totalOut,
        ]);
    }
}
