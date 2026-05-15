<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PrivateCashAdjustment;
use Illuminate\Http\Request;

class PrivateCashAdjustmentController extends Controller
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

        $rows = PrivateCashAdjustment::with('recorder:id,name')
            ->when(!$user->isAdmin(), fn($q) => $q->where('branch_id', $user->branch_id))
            ->when($request->date_from, fn($q, $d) => $q->whereDate('adjustment_date', '>=', $d))
            ->when($request->date_to,   fn($q, $d) => $q->whereDate('adjustment_date', '<=', $d))
            ->latest('adjustment_date')
            ->get();

        return response()->json($rows);
    }

    public function store(Request $request)
    {
        $this->authorise($request);
        $data = $request->validate([
            'adjustment_date' => 'required|date',
            'type'            => 'required|in:add,withdraw',
            'description'     => 'required|string|max:500',
            'amount'          => 'required|numeric|min:0.01',
            'payment_method'  => 'required|in:cash,bank_transfer',
            'notes'           => 'nullable|string',
        ]);

        $user = $request->user();
        $adj  = PrivateCashAdjustment::create([
            ...$data,
            'recorded_by' => $user->id,
            'branch_id'   => $user->branch_id,
        ]);

        return response()->json($adj->load('recorder:id,name'), 201);
    }

    public function update(Request $request, PrivateCashAdjustment $privateCashAdjustment)
    {
        $this->authorise($request);
        $data = $request->validate([
            'adjustment_date' => 'sometimes|date',
            'type'            => 'sometimes|in:add,withdraw',
            'description'     => 'sometimes|string|max:500',
            'amount'          => 'sometimes|numeric|min:0.01',
            'payment_method'  => 'sometimes|in:cash,bank_transfer',
            'notes'           => 'nullable|string',
        ]);

        $privateCashAdjustment->update($data);
        return response()->json($privateCashAdjustment->load('recorder:id,name'));
    }

    public function destroy(Request $request, PrivateCashAdjustment $privateCashAdjustment)
    {
        $this->authorise($request);
        $privateCashAdjustment->delete();
        return response()->json(['message' => 'Deleted']);
    }
}
