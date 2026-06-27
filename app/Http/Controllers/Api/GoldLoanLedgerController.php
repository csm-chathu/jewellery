<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GoldLoanLedger;
use Illuminate\Http\Request;

class GoldLoanLedgerController extends Controller
{
    public function index(Request $request)
    {
        $user  = $request->user();
        $rows  = GoldLoanLedger::query()
            ->when(!$user->isAdmin(), fn($q) => $q->where('branch_id', $user->branch_id))
            ->when($request->date_from, fn($q) => $q->where('entry_date', '>=', $request->date_from))
            ->when($request->date_to,   fn($q) => $q->where('entry_date', '<=', $request->date_to))
            ->orderBy('entry_date')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        // Compute running balance
        $balance = 0;
        $result  = $rows->map(function ($row) use (&$balance) {
            $balance = round($balance + ($row->weight ?? 0) - ($row->give_weight ?? 0), 3);
            $r = $row->toArray();
            $r['total_balance'] = $balance;
            return $r;
        });

        return response()->json($result);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'entry_date'  => 'required|date',
            'loan_rate'   => 'nullable|numeric|min:0',
            'loan_amount' => 'nullable|numeric|min:0',
            'weight'      => 'nullable|numeric',
            'description' => 'nullable|string|max:255',
            'give_weight' => 'nullable|numeric|min:0',
            'sort_order'  => 'integer',
        ]);

        $user = $request->user();
        $data['branch_id']  = $user->branch_id;
        $data['created_by'] = $user->id;

        return response()->json(GoldLoanLedger::create($data), 201);
    }

    public function update(Request $request, GoldLoanLedger $goldLoanLedger)
    {
        $data = $request->validate([
            'entry_date'  => 'required|date',
            'loan_rate'   => 'nullable|numeric|min:0',
            'loan_amount' => 'nullable|numeric|min:0',
            'weight'      => 'nullable|numeric',
            'description' => 'nullable|string|max:255',
            'give_weight' => 'nullable|numeric|min:0',
            'sort_order'  => 'integer',
        ]);

        $goldLoanLedger->update($data);
        return response()->json($goldLoanLedger);
    }

    public function destroy(GoldLoanLedger $goldLoanLedger)
    {
        $goldLoanLedger->delete();
        return response()->json(null, 204);
    }
}
