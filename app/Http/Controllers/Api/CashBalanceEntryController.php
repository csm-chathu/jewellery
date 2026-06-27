<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CashBalanceEntry;
use Illuminate\Http\Request;

class CashBalanceEntryController extends Controller
{
    public function index(Request $request)
    {
        $q = CashBalanceEntry::orderByDesc('entry_date')->orderByDesc('created_at');

        if ($request->filled('date_from')) {
            $q->where('entry_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $q->where('entry_date', '<=', $request->date_to);
        }

        return response()->json($q->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'entry_date'     => 'required|date',
            'entry_time'     => 'nullable|string',
            'jewellery_cash' => 'required|numeric|min:0',
            'old_cash'       => 'required|numeric|min:0',
            'actual_cash'    => 'nullable|numeric|min:0',
            'shots'          => 'nullable|array',
            'shots.*.name'   => 'required|string',
            'shots.*.amount' => 'required|numeric|min:0',
            'notes'          => 'nullable|string',
        ]);

        $data['created_by'] = $request->user()->id;
        $data['branch_id']  = $request->user()->branch_id ?? null;

        $entry = CashBalanceEntry::create($data);
        return response()->json($entry, 201);
    }

    public function update(Request $request, CashBalanceEntry $cashBalanceEntry)
    {
        $data = $request->validate([
            'entry_date'     => 'required|date',
            'entry_time'     => 'nullable|string',
            'jewellery_cash' => 'required|numeric|min:0',
            'old_cash'       => 'required|numeric|min:0',
            'actual_cash'    => 'nullable|numeric|min:0',
            'shots'          => 'nullable|array',
            'shots.*.name'   => 'required|string',
            'shots.*.amount' => 'required|numeric|min:0',
            'notes'          => 'nullable|string',
        ]);

        $cashBalanceEntry->update($data);
        return response()->json($cashBalanceEntry->fresh());
    }

    public function destroy(CashBalanceEntry $cashBalanceEntry)
    {
        $cashBalanceEntry->delete();
        return response()->json(['message' => 'Deleted']);
    }
}
