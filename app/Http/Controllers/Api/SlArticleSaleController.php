<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SlArticleSale;
use Illuminate\Http\Request;

class SlArticleSaleController extends Controller
{
    public function index(Request $request)
    {
        $user  = $request->user();
        $query = SlArticleSale::query()
            ->when(!$user->isAdmin(), fn($q) => $q->where('branch_id', $user->branch_id))
            ->when($request->date_from, fn($q) => $q->where('sale_date', '>=', $request->date_from))
            ->when($request->date_to,   fn($q) => $q->where('sale_date', '<=', $request->date_to))
            ->when($request->search,    fn($q) => $q->where('article', 'like', "%{$request->search}%"))
            ->orderBy('sale_date')
            ->orderBy('id');

        return response()->json($query->paginate(100));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'sale_date'  => 'required|date',
            'article'    => 'required|string|max:255',
            'weight'     => 'nullable|numeric|min:0',
            'gold_rate'  => 'required|numeric|min:0',
            'sale_price' => 'nullable|numeric',
            'profit'     => 'nullable|numeric',
            'notes'      => 'nullable|string',
        ]);

        $user = $request->user();
        $data['branch_id']  = $user->branch_id;
        $data['created_by'] = $user->id;

        return response()->json(SlArticleSale::create($data), 201);
    }

    public function update(Request $request, SlArticleSale $slArticleSale)
    {
        $data = $request->validate([
            'sale_date'  => 'required|date',
            'article'    => 'required|string|max:255',
            'weight'     => 'nullable|numeric|min:0',
            'gold_rate'  => 'required|numeric|min:0',
            'sale_price' => 'nullable|numeric',
            'profit'     => 'nullable|numeric',
            'notes'      => 'nullable|string',
        ]);

        $slArticleSale->update($data);
        return response()->json($slArticleSale);
    }

    public function destroy(SlArticleSale $slArticleSale)
    {
        $slArticleSale->delete();
        return response()->json(null, 204);
    }
}
