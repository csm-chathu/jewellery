<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PrivateExpense;
use Illuminate\Http\Request;

class PrivateExpenseController extends Controller
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

        $rows = PrivateExpense::with('recorder:id,name')
            ->when(!$user->isAdmin(), fn($q) => $q->where('branch_id', $user->branch_id))
            ->when($request->search, fn($q, $s) =>
                $q->where(function ($inner) use ($s) {
                    $inner->where('reference_number', 'like', "%$s%")
                          ->orWhere('description', 'like', "%$s%");
                })
            )
            ->when($request->category, fn($q, $c) => $q->where('category', $c))
            ->when($request->date_from, fn($q, $d) => $q->whereDate('expense_date', '>=', $d))
            ->when($request->date_to,   fn($q, $d) => $q->whereDate('expense_date', '<=', $d))
            ->latest('expense_date')
            ->paginate($request->per_page ?? 50);

        return response()->json($rows);
    }

    public function store(Request $request)
    {
        $this->authorise($request);
        $data = $request->validate([
            'expense_date'   => 'required|date',
            'category'       => 'required|in:transport,fees,commission,testing,tools,misc',
            'description'    => 'required|string|max:500',
            'amount'         => 'required|numeric|min:0.01',
            'payment_method' => 'required|in:cash,bank_transfer',
            'notes'          => 'nullable|string',
        ]);

        $user    = $request->user();
        $expense = PrivateExpense::create([
            ...$data,
            'recorded_by' => $user->id,
            'branch_id'   => $user->branch_id,
        ]);

        return response()->json($expense->load('recorder:id,name'), 201);
    }

    public function update(Request $request, PrivateExpense $privateExpense)
    {
        $this->authorise($request);
        $data = $request->validate([
            'expense_date'   => 'sometimes|date',
            'category'       => 'sometimes|in:transport,fees,commission,testing,tools,misc',
            'description'    => 'sometimes|string|max:500',
            'amount'         => 'sometimes|numeric|min:0.01',
            'payment_method' => 'sometimes|in:cash,bank_transfer',
            'notes'          => 'nullable|string',
        ]);

        $privateExpense->update($data);
        return response()->json($privateExpense->load('recorder:id,name'));
    }

    public function destroy(Request $request, PrivateExpense $privateExpense)
    {
        $this->authorise($request);
        $privateExpense->delete();
        return response()->json(['message' => 'Deleted']);
    }
}
