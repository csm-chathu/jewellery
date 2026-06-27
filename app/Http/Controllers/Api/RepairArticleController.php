<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RepairArticle;
use Illuminate\Http\Request;

class RepairArticleController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = RepairArticle::query()
            ->when(!$user->isAdmin(), fn($q) => $q->where('branch_id', $user->branch_id))
            ->when($request->search, fn($q) =>
                $q->where(fn($q2) =>
                    $q2->where('article', 'like', "%{$request->search}%")
                       ->orWhere('customer_name', 'like', "%{$request->search}%")
                       ->orWhere('bill_number', 'like', "%{$request->search}%")
                       ->orWhere('telephone', 'like', "%{$request->search}%")
                )
            )
            ->orderByDesc('received_date')
            ->orderByDesc('id');

        return response()->json($query->paginate(50));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'bill_number'   => 'nullable|string|max:100',
            'received_date' => 'required|date',
            'give_date'     => 'nullable|date',
            'article'       => 'required|string|max:255',
            'damage'        => 'nullable|string',
            'customer_name' => 'nullable|string|max:255',
            'telephone'     => 'nullable|string|max:30',
            'weight'        => 'nullable|numeric|min:0',
            'add_weight'    => 'nullable|numeric|min:0',
            'advance'       => 'nullable|numeric|min:0',
            'price'         => 'nullable|numeric|min:0',
            'done'          => 'boolean',
            'given'         => 'boolean',
            'notes'         => 'nullable|string',
        ]);

        $data['advance'] = $data['advance'] ?? 0;
        $data['price']   = $data['price']   ?? 0;

        $user = $request->user();
        $data['branch_id']  = $user->branch_id;
        $data['created_by'] = $user->id;

        return response()->json(RepairArticle::create($data), 201);
    }

    public function update(Request $request, RepairArticle $repairArticle)
    {
        $data = $request->validate([
            'bill_number'   => 'nullable|string|max:100',
            'received_date' => 'required|date',
            'give_date'     => 'nullable|date',
            'article'       => 'required|string|max:255',
            'damage'        => 'nullable|string',
            'customer_name' => 'nullable|string|max:255',
            'telephone'     => 'nullable|string|max:30',
            'weight'        => 'nullable|numeric|min:0',
            'add_weight'    => 'nullable|numeric|min:0',
            'advance'       => 'nullable|numeric|min:0',
            'price'         => 'nullable|numeric|min:0',
            'done'          => 'boolean',
            'given'         => 'boolean',
            'notes'         => 'nullable|string',
        ]);

        $data['advance'] = $data['advance'] ?? 0;
        $data['price']   = $data['price']   ?? 0;

        $repairArticle->update($data);
        return response()->json($repairArticle);
    }

    public function destroy(RepairArticle $repairArticle)
    {
        $repairArticle->delete();
        return response()->json(null, 204);
    }
}
