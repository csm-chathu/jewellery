<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\CustomMadeOrder;
use Illuminate\Http\Request;

class CustomMadeOrderController extends Controller
{
    private function authorise(Request $request): void
    {
        if (!in_array($request->user()->role, ['admin', 'manager', 'cashier', 'branch', 'gold_buyer'])) {
            abort(403, 'Access denied.');
        }
    }

    public function index(Request $request)
    {
        $this->authorise($request);

        $q = CustomMadeOrder::with(['customer:id,name,phone', 'creator:id,name'])
            ->orderByDesc('created_at');

        if ($request->status) {
            $q->where('status', $request->status);
        }

        if ($request->search) {
            $q->where(function ($sub) use ($request) {
                $sub->where('reference_number', 'like', "%{$request->search}%")
                    ->orWhere('description', 'like', "%{$request->search}%")
                    ->orWhere('customer_name', 'like', "%{$request->search}%")
                    ->orWhereHas('customer', fn($c) => $c->where('name', 'like', "%{$request->search}%"));
            });
        }

        return $q->paginate($request->per_page ?? 20);
    }

    public function store(Request $request)
    {
        $this->authorise($request);

        $data = $request->validate([
            'customer_id'        => 'nullable|exists:customers,id',
            'customer_name'      => 'nullable|string|max:255',
            'description'        => 'required|string|max:500',
            'drawing_image_url'  => 'nullable|url|max:1000',
            'estimated_weight'   => 'nullable|numeric|min:0',
            'karat'              => 'nullable|string|max:10',
            'gold_rate_per_gram' => 'nullable|numeric|min:0',
            'estimated_gold_cost'=> 'nullable|numeric|min:0',
            'making_charge'      => 'nullable|numeric|min:0',
            'other_charges'      => 'nullable|numeric|min:0',
            'advance_amount'     => 'nullable|numeric|min:0',
            'advance_paid_at'    => 'nullable|date',
            'expected_at'        => 'nullable|date',
            'notes'              => 'nullable|string',
        ]);

        $data['created_by'] = $request->user()->id;
        $data['branch_id']  = $request->user()->branch_id;
        $data['status']     = 'pending';

        $order = CustomMadeOrder::create($data);

        AuditLog::record('custom_order_created', "Custom order {$order->reference_number} created", $order, [], $data);

        return response()->json($order->load(['customer:id,name,phone', 'creator:id,name']), 201);
    }

    public function update(Request $request, CustomMadeOrder $customMadeOrder)
    {
        $this->authorise($request);

        if ($customMadeOrder->status === 'issued') {
            return response()->json(['message' => 'Issued orders cannot be edited.'], 422);
        }

        $data = $request->validate([
            'customer_id'        => 'nullable|exists:customers,id',
            'customer_name'      => 'nullable|string|max:255',
            'description'        => 'sometimes|string|max:500',
            'drawing_image_url'  => 'nullable|url|max:1000',
            'estimated_weight'   => 'nullable|numeric|min:0',
            'karat'              => 'nullable|string|max:10',
            'gold_rate_per_gram' => 'nullable|numeric|min:0',
            'estimated_gold_cost'=> 'nullable|numeric|min:0',
            'making_charge'      => 'nullable|numeric|min:0',
            'other_charges'      => 'nullable|numeric|min:0',
            'advance_amount'     => 'nullable|numeric|min:0',
            'advance_paid_at'    => 'nullable|date',
            'expected_at'        => 'nullable|date',
            'status'             => 'nullable|in:pending,in_progress',
            'notes'              => 'nullable|string',
        ]);

        $old = $customMadeOrder->only(['status', 'estimated_total']);
        $customMadeOrder->update($data);

        AuditLog::record('custom_order_updated', "Custom order {$customMadeOrder->reference_number} updated", $customMadeOrder, $old, $data);

        return response()->json($customMadeOrder->fresh(['customer:id,name,phone', 'creator:id,name']));
    }

    public function complete(Request $request, CustomMadeOrder $customMadeOrder)
    {
        $this->authorise($request);

        if (!in_array($customMadeOrder->status, ['pending', 'in_progress'])) {
            return response()->json(['message' => 'Order is already completed or issued.'], 422);
        }

        $data = $request->validate([
            'final_weight'        => 'required|numeric|min:0.001',
            'final_gold_cost'     => 'required|numeric|min:0',
            'final_making_charge' => 'required|numeric|min:0',
            'final_other_charges' => 'nullable|numeric|min:0',
            'notes'               => 'nullable|string',
        ]);

        $finalTotal = round(
            ($data['final_gold_cost'] ?? 0) +
            ($data['final_making_charge'] ?? 0) +
            ($data['final_other_charges'] ?? 0),
            2
        );

        $customMadeOrder->update([
            'final_weight'        => $data['final_weight'],
            'final_gold_cost'     => $data['final_gold_cost'],
            'final_making_charge' => $data['final_making_charge'],
            'final_other_charges' => $data['final_other_charges'] ?? 0,
            'final_total'         => $finalTotal,
            'balance_amount'      => round($finalTotal - ($customMadeOrder->advance_amount ?? 0), 2),
            'status'              => 'completed',
            'completed_at'        => now()->toDateString(),
            'notes'               => $data['notes'] ?? $customMadeOrder->notes,
        ]);

        AuditLog::record('custom_order_completed', "Custom order {$customMadeOrder->reference_number} marked complete", $customMadeOrder, [], $data);

        return response()->json($customMadeOrder->fresh(['customer:id,name,phone', 'creator:id,name']));
    }

    public function issue(Request $request, CustomMadeOrder $customMadeOrder)
    {
        $this->authorise($request);

        if ($customMadeOrder->status !== 'completed') {
            return response()->json(['message' => 'Order must be completed before issuing.'], 422);
        }

        $request->validate([
            'balance_collected' => 'required|numeric|min:0',
        ]);

        $customMadeOrder->update([
            'status'    => 'issued',
            'issued_at' => now()->toDateString(),
        ]);

        AuditLog::record('custom_order_issued', "Custom order {$customMadeOrder->reference_number} issued to customer", $customMadeOrder, [], [
            'balance_collected' => $request->balance_collected,
        ]);

        return response()->json($customMadeOrder->fresh(['customer:id,name,phone', 'creator:id,name']));
    }

    public function destroy(Request $request, CustomMadeOrder $customMadeOrder)
    {
        $this->authorise($request);

        if ($customMadeOrder->status === 'issued') {
            return response()->json(['message' => 'Issued orders cannot be deleted.'], 422);
        }

        AuditLog::record('custom_order_deleted', "Custom order {$customMadeOrder->reference_number} deleted", $customMadeOrder, $customMadeOrder->toArray(), []);
        $customMadeOrder->delete();

        return response()->noContent();
    }

    public function customers(Request $request)
    {
        $this->authorise($request);
        return Customer::select('id', 'name', 'phone')->orderBy('name')->limit(500)->get();
    }
}
