<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GoldBalanceEntry;
use Illuminate\Http\Request;

class GoldBalanceEntryController extends Controller
{
    public function index(Request $request)
    {
        $user  = $request->user();
        $query = GoldBalanceEntry::query()
            ->when(!$user->isAdmin(), fn($q) => $q->where('branch_id', $user->branch_id))
            ->when($request->date_from, fn($q) => $q->where('entry_date', '>=', $request->date_from))
            ->when($request->date_to,   fn($q) => $q->where('entry_date', '<=', $request->date_to))
            ->when($request->search, fn($q) =>
                $q->where(fn($q2) =>
                    $q2->where('description', 'like', "%{$request->search}%")
                       ->orWhere('article', 'like', "%{$request->search}%")
                )
            )
            ->orderBy('entry_date')
            ->orderBy('id');

        return response()->json($query->paginate(100));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'entry_date'    => 'required|date',
            'description'   => 'nullable|string|max:255',
            'karat'         => 'nullable|string|max:20',
            'give_weight_1' => 'nullable|numeric|min:0',
            'give_weight_2' => 'nullable|numeric|min:0',
            'give_weight_3' => 'nullable|numeric|min:0',
            'give_weight_4' => 'nullable|numeric|min:0',
            'article'       => 'nullable|string|max:255',
            'weight'        => 'nullable|numeric|min:0',
            'wastage'       => 'nullable|numeric|min:0',
            'other_charge'  => 'nullable|numeric|min:0',
        ]);

        $user = $request->user();
        $data['branch_id']  = $user->branch_id;
        $data['created_by'] = $user->id;

        return response()->json(GoldBalanceEntry::create($data), 201);
    }

    public function update(Request $request, GoldBalanceEntry $goldBalanceEntry)
    {
        $data = $request->validate([
            'entry_date'    => 'required|date',
            'description'   => 'nullable|string|max:255',
            'karat'         => 'nullable|string|max:20',
            'give_weight_1' => 'nullable|numeric|min:0',
            'give_weight_2' => 'nullable|numeric|min:0',
            'give_weight_3' => 'nullable|numeric|min:0',
            'give_weight_4' => 'nullable|numeric|min:0',
            'article'       => 'nullable|string|max:255',
            'weight'        => 'nullable|numeric|min:0',
            'wastage'       => 'nullable|numeric|min:0',
            'other_charge'  => 'nullable|numeric|min:0',
        ]);

        $goldBalanceEntry->update($data);
        return response()->json($goldBalanceEntry);
    }

    public function destroy(GoldBalanceEntry $goldBalanceEntry)
    {
        $goldBalanceEntry->delete();
        return response()->json(null, 204);
    }
}
