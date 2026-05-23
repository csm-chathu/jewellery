<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PrivateBuyer;
use Illuminate\Http\Request;

class PrivateBuyerController extends Controller
{
    public function index(Request $request)
    {
        $q = PrivateBuyer::query()->orderBy('name');

        if ($search = $request->query('search')) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('phone', 'like', "%{$search}%");
        }

        return response()->json($q->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'  => 'required|string|max:191',
            'phone' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
        ]);

        return response()->json(PrivateBuyer::create($data), 201);
    }

    public function update(Request $request, PrivateBuyer $privateBuyer)
    {
        $data = $request->validate([
            'name'  => 'required|string|max:191',
            'phone' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
        ]);

        $privateBuyer->update($data);
        return response()->json($privateBuyer);
    }

    public function destroy(PrivateBuyer $privateBuyer)
    {
        $privateBuyer->delete();
        return response()->json(null, 204);
    }
}
