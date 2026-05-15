<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InformalGoldPurchase;
use Illuminate\Http\Request;

class InformalPurchaseController extends Controller
{
    public function index(Request $request)
    {
        $this->authorise($request);

        $q = InformalGoldPurchase::with('recorder:id,name')
            ->when($request->search, fn($q, $s) =>
                $q->where('reference_number', 'like', "%$s%")
                  ->orWhere('description', 'like', "%$s%")
                  ->orWhere('notes', 'like', "%$s%")
            )
            ->when($request->date_from, fn($q, $d) => $q->whereDate('purchase_date', '>=', $d))
            ->when($request->date_to,   fn($q, $d) => $q->whereDate('purchase_date', '<=', $d))
            ->latest('purchase_date')
            ->latest('id');

        return response()->json($q->paginate($request->per_page ?? 25));
    }

    public function store(Request $request)
    {
        $this->authorise($request);

        $data = $request->validate([
            'purchase_date'    => 'required|date',
            'description'      => 'nullable|string|max:255',
            'item_type'        => 'required|in:jewelry,coin,bar,scrap,other',
            'gross_weight'     => 'required|numeric|min:0',
            'deduction_weight' => 'nullable|numeric|min:0',
            'net_weight'       => 'required|numeric|min:0',
            'declared_karat'   => 'nullable|string|max:10',
            'rate_per_gram'    => 'required|numeric|min:0',
            'final_price'      => 'required|numeric|min:0',
            'payment_method'   => 'required|in:cash,bank_transfer',
            'notes'             => 'nullable|string|max:1000',
            'nic_front_url'     => 'nullable|string|max:500',
            'nic_back_url'      => 'nullable|string|max:500',
            'invoice_photo_url' => 'nullable|string|max:500',
        ]);

        $data['recorded_by'] = $request->user()->id;
        $data['branch_id']   = $request->user()->branch_id;

        $record = InformalGoldPurchase::create($data);

        return response()->json($record->load('recorder:id,name'), 201);
    }

    public function update(Request $request, InformalGoldPurchase $informalGoldPurchase)
    {
        $this->authorise($request);

        $data = $request->validate([
            'purchase_date'    => 'sometimes|date',
            'description'      => 'nullable|string|max:255',
            'item_type'        => 'sometimes|in:jewelry,coin,bar,scrap,other',
            'gross_weight'     => 'sometimes|numeric|min:0',
            'deduction_weight' => 'nullable|numeric|min:0',
            'net_weight'       => 'sometimes|numeric|min:0',
            'declared_karat'   => 'nullable|string|max:10',
            'rate_per_gram'    => 'sometimes|numeric|min:0',
            'final_price'      => 'sometimes|numeric|min:0',
            'payment_method'   => 'sometimes|in:cash,bank_transfer',
            'notes'             => 'nullable|string|max:1000',
            'nic_front_url'     => 'nullable|string|max:500',
            'nic_back_url'      => 'nullable|string|max:500',
            'invoice_photo_url' => 'nullable|string|max:500',
        ]);

        $informalGoldPurchase->update($data);

        return response()->json($informalGoldPurchase->load('recorder:id,name'));
    }

    public function destroy(Request $request, InformalGoldPurchase $informalGoldPurchase)
    {
        $this->authorise($request);
        $informalGoldPurchase->delete();
        return response()->noContent();
    }

    private function authorise(Request $request): void
    {
        $role = $request->user()->role ?? '';
        if ($role !== 'gold_buyer') {
            abort(403, 'Access restricted.');
        }
    }
}
