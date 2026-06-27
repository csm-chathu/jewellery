<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GoldListUdaya;
use Illuminate\Http\Request;

class GoldListUdayaController extends Controller
{
    public function index(Request $request)
    {
        $user  = $request->user();
        $query = GoldListUdaya::query()
            ->when(!$user->isAdmin(), fn($q) => $q->where('branch_id', $user->branch_id))
            ->when($request->date_from, fn($q) => $q->whereDate('created_at', '>=', $request->date_from))
            ->when($request->date_to,   fn($q) => $q->whereDate('created_at', '<=', $request->date_to))
            ->when($request->search, fn($q) =>
                $q->where(fn($q2) =>
                    $q2->where('item', 'like', "%{$request->search}%")
                       ->orWhere('description', 'like', "%{$request->search}%")
                )
            )
            ->orderBy('id');

        return response()->json($query->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'item'          => 'required|string|max:255',
            'description'   => 'nullable|string|max:255',
            'weight'        => 'nullable|numeric|min:0',
            'stock_weight'  => 'nullable|numeric|min:0',
            'price'         => 'nullable|numeric|min:0',
            'average_karat' => 'nullable|string|max:20',
            'moose_pay'     => 'nullable|numeric|min:0',
        ]);

        $user = $request->user();
        $data['branch_id']  = $user->branch_id;
        $data['created_by'] = $user->id;

        return response()->json(GoldListUdaya::create($data), 201);
    }

    public function update(Request $request, GoldListUdaya $goldListUdaya)
    {
        $data = $request->validate([
            'item'          => 'required|string|max:255',
            'description'   => 'nullable|string|max:255',
            'weight'        => 'nullable|numeric|min:0',
            'stock_weight'  => 'nullable|numeric|min:0',
            'price'         => 'nullable|numeric|min:0',
            'average_karat' => 'nullable|string|max:20',
            'moose_pay'     => 'nullable|numeric|min:0',
        ]);

        $goldListUdaya->update($data);
        return response()->json($goldListUdaya);
    }

    public function destroy(GoldListUdaya $goldListUdaya)
    {
        $goldListUdaya->delete();
        return response()->json(null, 204);
    }
}
