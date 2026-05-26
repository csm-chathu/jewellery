<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;

class PrivateBuyerController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $q = Customer::query()->orderBy('name');

        if (!$user->isAdmin()) {
            $q->where('branch_id', $user->branch_id);
        }

        if ($search = $request->query('search')) {
            $q->where(function ($inner) use ($search) {
                $inner->where('name', 'like', "%{$search}%")
                      ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        return response()->json($q->get(['id', 'name', 'phone', 'address']));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'    => 'required|string|max:150',
            'phone'   => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'notes'   => 'nullable|string',
        ]);

        $data['branch_id'] = $request->user()->branch_id;

        $customer = Customer::create($data);

        return response()->json($customer->only(['id', 'name', 'phone', 'address']), 201);
    }

    public function update(Request $request, Customer $customer)
    {
        $data = $request->validate([
            'name'    => 'required|string|max:150',
            'phone'   => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'notes'   => 'nullable|string',
        ]);

        $customer->update($data);

        return response()->json($customer->only(['id', 'name', 'phone', 'address']));
    }

    public function destroy(Customer $customer)
    {
        $customer->delete();
        return response()->json(null, 204);
    }
}
