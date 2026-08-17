<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PrivateGoldLoan;
use App\Models\PrivateGoldLoanRepayment;
use Illuminate\Http\Request;

class PrivateGoldLoanController extends Controller
{
    private function authorise(Request $request): void
    {
        if (($request->user()->role ?? '') !== 'gold_buyer') {
            abort(403, 'Access restricted.');
        }
    }

    public function index(Request $request)
    {
        $this->authorise($request);
        $user = $request->user();

        $loans = PrivateGoldLoan::with('repayments')
            ->when(!$user->isAdmin(), fn($q) => $q->where('branch_id', $user->branch_id))
            ->when($request->status, fn($q, $s) => $q->where('status', $s))
            ->when($request->search, fn($q, $s) => $q->where(function ($q) use ($s) {
                $q->where('borrower_name', 'like', "%$s%")
                  ->orWhere('loan_number', 'like', "%$s%")
                  ->orWhere('borrower_nic', 'like', "%$s%");
            }))
            ->latest('disbursed_date')
            ->get();

        return response()->json($loans);
    }

    public function store(Request $request)
    {
        $this->authorise($request);
        $data = $request->validate([
            'borrower_name'         => 'required|string|max:150',
            'borrower_nic'          => 'nullable|string|max:20',
            'borrower_phone'        => 'nullable|string|max:20',
            'pledge_description'    => 'nullable|string',
            'declared_karat'        => 'nullable|string|max:10',
            'gross_weight'          => 'nullable|numeric|min:0',
            'net_weight'            => 'nullable|numeric|min:0',
            'loan_amount'           => 'required|numeric|min:0.01',
            'interest_rate_monthly' => 'nullable|numeric|min:0',
            'disbursed_date'        => 'required|date',
            'maturity_date'         => 'nullable|date',
            'notes'                 => 'nullable|string',
        ]);

        $user = $request->user();
        $loan = PrivateGoldLoan::create([
            ...$data,
            'loan_number'           => PrivateGoldLoan::generateNumber(),
            'outstanding_principal' => $data['loan_amount'],
            'branch_id'             => $user->branch_id,
            'recorded_by'           => $user->id,
        ]);

        return response()->json($loan->load('repayments'), 201);
    }

    public function update(Request $request, PrivateGoldLoan $privateGoldLoan)
    {
        $this->authorise($request);
        $data = $request->validate([
            'borrower_name'         => 'sometimes|string|max:150',
            'borrower_nic'          => 'nullable|string|max:20',
            'borrower_phone'        => 'nullable|string|max:20',
            'pledge_description'    => 'nullable|string',
            'declared_karat'        => 'nullable|string|max:10',
            'gross_weight'          => 'nullable|numeric|min:0',
            'net_weight'            => 'nullable|numeric|min:0',
            'interest_rate_monthly' => 'nullable|numeric|min:0',
            'disbursed_date'        => 'sometimes|date',
            'maturity_date'         => 'nullable|date',
            'status'                => 'sometimes|in:active,closed,overdue',
            'notes'                 => 'nullable|string',
        ]);

        $privateGoldLoan->update($data);
        return response()->json($privateGoldLoan->load('repayments'));
    }

    public function destroy(Request $request, PrivateGoldLoan $privateGoldLoan)
    {
        $this->authorise($request);
        $privateGoldLoan->repayments()->delete();
        $privateGoldLoan->delete();
        return response()->json(['message' => 'Deleted']);
    }

    public function storeRepayment(Request $request, PrivateGoldLoan $privateGoldLoan)
    {
        $this->authorise($request);
        $data = $request->validate([
            'payment_date'    => 'required|date',
            'principal_paid'  => 'required|numeric|min:0',
            'interest_paid'   => 'nullable|numeric|min:0',
            'notes'           => 'nullable|string',
        ]);

        $data['interest_paid'] = $data['interest_paid'] ?? 0;
        $data['total_paid']    = $data['principal_paid'] + $data['interest_paid'];
        $data['recorded_by']   = $request->user()->id;

        $repayment = $privateGoldLoan->repayments()->create($data);

        $newOutstanding = max(0, $privateGoldLoan->outstanding_principal - $data['principal_paid']);
        $privateGoldLoan->update([
            'outstanding_principal' => $newOutstanding,
            'status' => $newOutstanding <= 0 ? 'closed' : $privateGoldLoan->status,
        ]);

        return response()->json($privateGoldLoan->load('repayments'), 201);
    }

    public function destroyRepayment(Request $request, PrivateGoldLoan $privateGoldLoan, PrivateGoldLoanRepayment $repayment)
    {
        $this->authorise($request);
        $privateGoldLoan->increment('outstanding_principal', $repayment->principal_paid);
        if ($privateGoldLoan->status === 'closed') {
            $privateGoldLoan->update(['status' => 'active']);
        }
        $repayment->delete();
        return response()->json($privateGoldLoan->load('repayments'));
    }
}
