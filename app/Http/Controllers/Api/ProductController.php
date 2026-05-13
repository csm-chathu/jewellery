<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function index()
    {
        $user = request()->user();
        $products = Product::with(['category:id,name', 'supplier:id,name'])
            ->when(!$user->isAdmin(), fn($q) => $q->where('branch_id', $user->branch_id))
            ->when(request('search'), fn($q, $s) => $q->where(function ($inner) use ($s) {
                $inner->where('name', 'like', "%$s%")
                    ->orWhere('sku', 'like', "%$s%")
                    ->orWhere('barcode', 'like', "%$s%");
            }))
            ->when(request('category_id'), fn($q, $c) => $q->where('category_id', $c))
            ->when(request('low_stock'), fn($q) => $q->whereColumn('stock_quantity', '<=', 'min_stock_level'))
            ->latest()
            ->paginate(request('per_page', 20));
        return response()->json($products);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'           => 'required|string|max:200',
            'description'    => 'nullable|string',
            'category_id'    => 'required|exists:categories,id',
            'material'       => 'nullable|string|max:50',
            'weight'         => 'nullable|numeric|min:0',
            'karat'          => 'nullable|string|max:10',
            'size'           => 'nullable|string|max:50',
            'color'          => 'nullable|string|max:50',
            'gemstone'       => 'nullable|string|max:100',
            'purchase_price' => 'required|numeric|min:0',
            'selling_price'  => 'required|numeric|min:0',
            'stock_quantity' => 'required|integer|min:0',
            'min_stock_level'=> 'required|integer|min:0',
            'is_active'      => 'boolean',
            'supplier_id'    => 'nullable|exists:suppliers,id',
            'barcode'        => 'nullable|string|max:100|unique:products,barcode',
            'image'          => 'nullable|image|max:2048',
            'image_url'      => 'nullable|url|max:1000',
            'image_public_id'=> 'nullable|string|max:255',
        ]);

        $data['sku'] = strtoupper('JEW-' . Str::random(8));
        $data['branch_id'] = $request->user()->branch_id;

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('products', 'public');
        } elseif (!empty($data['image_url'])) {
            $data['image'] = $data['image_url'];
            $data['image_public_id'] = $data['image_public_id'] ?? null;
        }

        unset($data['image_url']);

        return response()->json(Product::create($data), 201);
    }

    public function show(Product $product)
    {
        $this->authorizeBranch($product->branch_id);
        return response()->json($product->load(['category', 'supplier']));
    }

    public function update(Request $request, Product $product)
    {
        $data = $request->validate([
            'name'           => 'required|string|max:200',
            'description'    => 'nullable|string',
            'category_id'    => 'required|exists:categories,id',
            'material'       => 'nullable|string|max:50',
            'weight'         => 'nullable|numeric|min:0',
            'karat'          => 'nullable|string|max:10',
            'size'           => 'nullable|string|max:50',
            'color'          => 'nullable|string|max:50',
            'gemstone'       => 'nullable|string|max:100',
            'purchase_price' => 'required|numeric|min:0',
            'selling_price'  => 'required|numeric|min:0',
            'stock_quantity' => 'required|integer|min:0',
            'min_stock_level'=> 'required|integer|min:0',
            'is_active'      => 'boolean',
            'supplier_id'    => 'nullable|exists:suppliers,id',
            'barcode'        => 'nullable|string|max:100|unique:products,barcode,' . $product->id,
            'image'          => 'nullable|image|max:2048',
            'image_url'      => 'nullable|url|max:1000',
            'image_public_id'=> 'nullable|string|max:255',
        ]);

        if ($request->hasFile('image')) {
            if ($product->image && !$this->isExternalUrl($product->image)) {
                Storage::disk('public')->delete($product->image);
            }
            $data['image'] = $request->file('image')->store('products', 'public');
            $data['image_public_id'] = null;
        } elseif (!empty($data['image_url'])) {
            if ($product->image && !$this->isExternalUrl($product->image)) {
                Storage::disk('public')->delete($product->image);
            }
            $data['image'] = $data['image_url'];
            $data['image_public_id'] = $data['image_public_id'] ?? null;
        }

        unset($data['image_url']);

        $this->authorizeBranch($product->branch_id);
        $product->update($data);
        return response()->json($product->fresh(['category', 'supplier']));
    }

    public function destroy(Product $product)
    {
        $this->authorizeBranch($product->branch_id);
        if ($product->image && !$this->isExternalUrl($product->image)) {
            Storage::disk('public')->delete($product->image);
        }
        $product->delete();
        return response()->json(['message' => 'Product deleted']);
    }

    private function isExternalUrl(string $path): bool
    {
        return str_starts_with($path, 'http://') || str_starts_with($path, 'https://');
    }

    private function authorizeBranch(?int $branchId): void
    {
        $user = request()->user();
        if (!$user->isAdmin() && $user->branch_id !== $branchId) {
            abort(403, 'Forbidden for this branch.');
        }
    }

}
