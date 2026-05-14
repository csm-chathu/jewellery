<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Carat;
use Illuminate\Http\Request;

class CaratController extends Controller
{
    public function index()
    {
        return response()->json(Carat::orderBy('sort_order')->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'label'      => 'required|string|max:10|unique:carats,label',
            'purity'     => 'required|numeric|min:0.0001|max:1',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $carat = Carat::create(array_merge($data, ['is_active' => true]));

        return response()->json($carat, 201);
    }

    public function destroy(Carat $carat)
    {
        if ($carat->goldRates()->exists()) {
            return response()->json([
                'message' => "Cannot delete \"{$carat->label}\" — it has existing rate records.",
            ], 422);
        }

        $carat->delete();

        return response()->json(null, 204);
    }

    public function toggle(Carat $carat)
    {
        $carat->update(['is_active' => !$carat->is_active]);

        return response()->json($carat);
    }
}
