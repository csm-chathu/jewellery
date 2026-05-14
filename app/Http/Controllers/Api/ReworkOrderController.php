<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\GoldBuyback;
use App\Models\Product;
use App\Models\ReworkOrder;
use App\Models\ScrapItem;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ReworkOrderController extends Controller
{
    private function authorise(Request $request): void
    {
        if (!in_array($request->user()->role, ['admin', 'manager', 'cashier', 'branch'])) {
            abort(403, 'Access denied.');
        }
    }

    public function index(Request $request)
    {
        $this->authorise($request);

        $q = ReworkOrder::with([
            'buyback:id,buyback_number,customer_id',
            'buyback.customer:id,name',
            'scrapItem:id,sku,description',
            'outputProduct:id,name,sku',
            'creator:id,name',
        ])->orderByDesc('created_at');

        if ($request->status) {
            $q->where('status', $request->status);
        }

        if ($request->search) {
            $q->where(function ($sub) use ($request) {
                $sub->where('reference_number', 'like', "%{$request->search}%")
                    ->orWhere('description', 'like', "%{$request->search}%")
                    ->orWhere('goldsmith_name', 'like', "%{$request->search}%");
            });
        }

        if (!$request->user()->isAdmin() && $request->user()->branch_id) {
            $q->where('branch_id', $request->user()->branch_id);
        }

        return $q->paginate($request->per_page ?? 20);
    }

    public function store(Request $request)
    {
        $this->authorise($request);

        $data = $request->validate([
            'source_type'         => 'required|in:buyback,scrap,manual',
            'buyback_id'          => 'nullable|exists:gold_buybacks,id',
            'scrap_item_id'       => 'nullable|exists:scrap_items,id',
            'description'         => 'required|string|max:255',
            'goldsmith_name'      => 'nullable|string|max:255',
            'input_weight'        => 'required|numeric|min:0',
            'input_karat'         => 'nullable|string|max:10',
            'input_gold_cost'     => 'required|numeric|min:0',
            'added_gold_weight'   => 'nullable|numeric|min:0',
            'added_gold_cost'     => 'nullable|numeric|min:0',
            'making_charge'       => 'nullable|numeric|min:0',
            'making_charge_notes' => 'nullable|string|max:255',
            'started_at'          => 'nullable|date',
            'expected_at'         => 'nullable|date',
            'status'              => 'nullable|in:pending,in_progress',
            'notes'               => 'nullable|string',
        ]);

        $data['created_by'] = $request->user()->id;
        $data['branch_id']  = $request->user()->branch_id;
        $data['status']     = $data['status'] ?? 'pending';

        $order = ReworkOrder::create($data);

        AuditLog::record('rework_created', "Rework order {$order->reference_number} created", $order, [], $data);

        return response()->json($order->load(['buyback.customer:id,name', 'scrapItem:id,sku', 'creator:id,name']), 201);
    }

    public function update(Request $request, ReworkOrder $reworkOrder)
    {
        $this->authorise($request);

        if ($reworkOrder->status === 'completed') {
            return response()->json(['message' => 'Completed orders cannot be edited.'], 422);
        }

        $data = $request->validate([
            'description'         => 'sometimes|string|max:255',
            'goldsmith_name'      => 'nullable|string|max:255',
            'input_weight'        => 'sometimes|numeric|min:0',
            'input_karat'         => 'nullable|string|max:10',
            'input_gold_cost'     => 'sometimes|numeric|min:0',
            'added_gold_weight'   => 'nullable|numeric|min:0',
            'added_gold_cost'     => 'nullable|numeric|min:0',
            'making_charge'       => 'nullable|numeric|min:0',
            'making_charge_notes' => 'nullable|string|max:255',
            'started_at'          => 'nullable|date',
            'expected_at'         => 'nullable|date',
            'status'              => 'nullable|in:pending,in_progress,cancelled',
            'notes'               => 'nullable|string',
        ]);

        $old = $reworkOrder->only(['status', 'total_cost']);
        $reworkOrder->update($data);

        AuditLog::record('rework_updated', "Rework order {$reworkOrder->reference_number} updated", $reworkOrder, $old, $data);

        return response()->json($reworkOrder->fresh(['buyback.customer:id,name', 'scrapItem:id,sku', 'creator:id,name']));
    }

    public function complete(Request $request, ReworkOrder $reworkOrder)
    {
        $this->authorise($request);

        if ($reworkOrder->status === 'completed') {
            return response()->json(['message' => 'Already completed.'], 422);
        }

        $data = $request->validate([
            'output_weight'   => 'required|numeric|min:0.001',
            'output_karat'    => 'required|string|max:10',
            'product_name'    => 'required|string|max:255',
            'category_id'     => 'nullable|exists:categories,id',
            'selling_price'   => 'required|numeric|min:0',
            'making_charge'   => 'nullable|numeric|min:0',
            'making_charge_notes' => 'nullable|string|max:255',
        ]);

        // Update making charge if provided
        if (isset($data['making_charge'])) {
            $reworkOrder->making_charge       = $data['making_charge'];
            $reworkOrder->making_charge_notes = $data['making_charge_notes'] ?? $reworkOrder->making_charge_notes;
        }

        // Create the new product
        $product = Product::create([
            'sku'            => 'RWK-' . strtoupper(Str::random(7)),
            'name'           => $data['product_name'],
            'category_id'    => $data['category_id'] ?? null,
            'weight'         => $data['output_weight'],
            'karat'          => $data['output_karat'],
            'material'       => 'gold',
            'purchase_price' => $reworkOrder->total_cost,
            'selling_price'  => $data['selling_price'],
            'stock_quantity' => 1,
            'min_stock_level'=> 1,
            'is_active'      => true,
            'branch_id'      => $reworkOrder->branch_id,
        ]);

        // Mark scrap item as melted if source is scrap
        if ($reworkOrder->source_type === 'scrap' && $reworkOrder->scrap_item_id) {
            ScrapItem::where('id', $reworkOrder->scrap_item_id)->update(['status' => 'melted']);
        }

        // Complete the rework order
        $reworkOrder->update([
            'output_weight'      => $data['output_weight'],
            'output_karat'       => $data['output_karat'],
            'output_product_id'  => $product->id,
            'completed_at'       => now()->toDateString(),
            'status'             => 'completed',
        ]);

        AuditLog::record('rework_completed', "Rework {$reworkOrder->reference_number} completed → Product {$product->sku}", $reworkOrder, [], [
            'product_id'    => $product->id,
            'output_weight' => $data['output_weight'],
            'selling_price' => $data['selling_price'],
        ]);

        return response()->json([
            'rework_order' => $reworkOrder->fresh(['outputProduct', 'creator:id,name']),
            'product'      => $product,
        ]);
    }

    public function destroy(Request $request, ReworkOrder $reworkOrder)
    {
        $this->authorise($request);

        if ($reworkOrder->status === 'completed') {
            return response()->json(['message' => 'Completed orders cannot be deleted.'], 422);
        }

        AuditLog::record('rework_deleted', "Rework order {$reworkOrder->reference_number} deleted", $reworkOrder, $reworkOrder->toArray(), []);
        $reworkOrder->delete();

        return response()->noContent();
    }

    public function buybackOptions(Request $request)
    {
        $this->authorise($request);
        return GoldBuyback::with('customer:id,name')
            ->select('id', 'buyback_number', 'customer_id', 'net_weight', 'declared_karat', 'final_price', 'status')
            ->where('status', 'completed')
            ->latest()
            ->limit(100)
            ->get();
    }

    public function scrapOptions(Request $request)
    {
        $this->authorise($request);
        return ScrapItem::select('id', 'sku', 'description', 'karat', 'weight_g', 'estimated_value', 'status')
            ->where('status', 'available')
            ->latest()
            ->limit(100)
            ->get();
    }

    public function categories(Request $request)
    {
        $this->authorise($request);
        return Category::select('id', 'name')->orderBy('name')->get();
    }
}
