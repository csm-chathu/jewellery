<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\AuditLog;
use App\Models\GoldLoan;
use App\Models\GoldLoanRepayment;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GoldLoanController extends Controller
{
    public function index(Request $request)
    {
        $today = Carbon::today();

        GoldLoan::query()
            ->where('status', 'active')
            ->whereDate('maturity_date', '<', $today)
            ->update(['status' => 'overdue']);

        return GoldLoan::with([
            'customer:id,name,phone',
            'loanReceivableAccount:id,code,name',
            'disbursedFromAccount:id,code,name',
        ])
            ->when($request->status, fn($q, $v) => $q->where('status', $v))
            ->when($request->search, fn($q, $v) => $q->where(function ($x) use ($v) {
                $x->where('loan_number', 'like', "%{$v}%")
                    ->orWhereHas('customer', fn($c) => $c->where('name', 'like', "%{$v}%"));
            }))
            ->orderByDesc('disbursed_date')
            ->paginate(20);
    }

    public function show(GoldLoan $goldLoan)
    {
        $goldLoan->load([
            'customer:id,name,phone,address',
            'repayments' => fn($q) => $q->with(['receivedToAccount:id,code,name'])->latest('payment_date')->limit(30),
            'loanReceivableAccount:id,code,name',
            'disbursedFromAccount:id,code,name',
            'journalEntry:id,entry_number',
        ]);

        $goldLoan->setAttribute('accrued_interest', $this->calculateAccruedInterest($goldLoan, Carbon::today()));
        $goldLoan->setAttribute('payoff_amount', round($goldLoan->outstanding_principal + $goldLoan->accrued_interest, 2));

        return response()->json($goldLoan);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'customer_id'               => 'required|exists:customers,id',
            'pledge_description'        => 'required|string|max:255',
            'item_type'                 => 'required|in:jewelry,coin,bar,scrap,other',
            'gross_weight'              => 'required|numeric|min:0',
            'deduction_weight'          => 'nullable|numeric|min:0',
            'net_weight'                => 'required|numeric|min:0',
            'declared_karat'            => 'required|in:9k,14k,18k,22k,24k,unknown',
            'loan_amount'               => 'required|numeric|min:1',
            'interest_rate_monthly'     => 'required|numeric|min:0|max:100',
            'disbursed_date'            => 'required|date',
            'maturity_date'             => 'nullable|date|after_or_equal:disbursed_date',
            'loan_receivable_account_id'=> 'nullable|exists:accounts,id',
            'disbursed_from_account_id' => 'required|exists:accounts,id',
            'notes'                     => 'nullable|string|max:1000',
        ]);

        DB::beginTransaction();
        try {
            $seq = GoldLoan::withTrashed()->count() + 1;
            $loan = GoldLoan::create([
                ...$data,
                'loan_number' => 'GLN-' . date('Ym') . '-' . str_pad($seq, 4, '0', STR_PAD_LEFT),
                'deduction_weight' => $data['deduction_weight'] ?? 0,
                'maturity_date' => !empty($data['maturity_date'])
                    ? Carbon::parse($data['maturity_date'])
                    : Carbon::parse($data['disbursed_date'])->addMonths(3),
                'loan_receivable_account_id' => $data['loan_receivable_account_id'] ?? Account::where('code', '1110')->value('id'),
                'outstanding_principal' => $data['loan_amount'],
                'last_interest_date' => $data['disbursed_date'],
                'status' => 'active',
                'branch_id' => auth()->user()->branch_id,
                'user_id' => auth()->id(),
            ]);

            $entry = $this->postDisbursementJournal($loan);
            $loan->update(['journal_entry_id' => $entry->id]);

            AuditLog::record('gold_loan_created', "Gold loan {$loan->loan_number} created", $loan);
            DB::commit();

            return response()->json($loan->load(['customer:id,name', 'journalEntry:id,entry_number']), 201);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to create gold loan: ' . $e->getMessage()], 500);
        }
    }

    public function repay(Request $request, GoldLoan $goldLoan)
    {
        if (!in_array($goldLoan->status, ['active', 'overdue'])) {
            return response()->json(['message' => 'Only active/overdue loans can be repaid.'], 422);
        }

        $data = $request->validate([
            'payment_date'              => 'required|date',
            'amount'                    => 'required|numeric|min:0.01',
            'received_to_account_id'    => 'required|exists:accounts,id',
            'interest_income_account_id'=> 'nullable|exists:accounts,id',
            'close_loan'                => 'nullable|boolean',
            'notes'                     => 'nullable|string|max:1000',
        ]);

        $paymentDate = Carbon::parse($data['payment_date']);
        $accruedInterest = $this->calculateAccruedInterest($goldLoan, $paymentDate);

        $amount = round((float) $data['amount'], 2);
        $interestComponent = round(min($amount, $accruedInterest), 2);
        $principalComponent = round(max(0, $amount - $interestComponent), 2);

        if ($principalComponent > $goldLoan->outstanding_principal) {
            $principalComponent = $goldLoan->outstanding_principal;
            $interestComponent = round($amount - $principalComponent, 2);
        }

        DB::beginTransaction();
        try {
            $seq = GoldLoanRepayment::withTrashed()->count() + 1;
            $repayment = GoldLoanRepayment::create([
                'payment_number' => 'GLP-' . date('Ym') . '-' . str_pad($seq, 4, '0', STR_PAD_LEFT),
                'gold_loan_id' => $goldLoan->id,
                'payment_date' => $paymentDate,
                'amount' => $amount,
                'interest_component' => $interestComponent,
                'principal_component' => $principalComponent,
                'received_to_account_id' => $data['received_to_account_id'],
                'interest_income_account_id' => $data['interest_income_account_id'] ?? Account::where('code', '4300')->value('id'),
                'notes' => $data['notes'] ?? null,
                'branch_id' => auth()->user()->branch_id,
                'user_id' => auth()->id(),
            ]);

            $entry = $this->postRepaymentJournal($goldLoan, $repayment);
            $repayment->update(['journal_entry_id' => $entry->id]);

            $remainingPrincipal = round(max(0, $goldLoan->outstanding_principal - $principalComponent), 2);
            $shouldClose = ($data['close_loan'] ?? false) || $remainingPrincipal <= 0.01;

            $goldLoan->update([
                'outstanding_principal' => $remainingPrincipal,
                'last_interest_date' => $paymentDate,
                'status' => $shouldClose ? 'closed' : ($goldLoan->maturity_date < $paymentDate ? 'overdue' : 'active'),
            ]);

            AuditLog::record('gold_loan_repaid', "Gold loan payment {$repayment->payment_number} posted", $goldLoan);
            DB::commit();

            return response()->json($repayment->load(['receivedToAccount:id,code,name', 'journalEntry:id,entry_number']), 201);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to post repayment: ' . $e->getMessage()], 500);
        }
    }

    public function reminders(Request $request)
    {
        $withinDays = (int) ($request->within_days ?? 7);
        $today = Carbon::today();
        $horizon = $today->copy()->addDays($withinDays);

        $rows = GoldLoan::with('customer:id,name,phone')
            ->whereIn('status', ['active', 'overdue'])
            ->whereDate('maturity_date', '<=', $horizon)
            ->orderBy('maturity_date')
            ->get()
            ->map(function (GoldLoan $loan) use ($today) {
                $daysLeft = $today->diffInDays($loan->maturity_date, false);
                return [
                    'id' => $loan->id,
                    'loan_number' => $loan->loan_number,
                    'customer_name' => $loan->customer?->name,
                    'customer_phone' => $loan->customer?->phone,
                    'maturity_date' => $loan->maturity_date?->toDateString(),
                    'days_left' => $daysLeft,
                    'status' => $daysLeft < 0 ? 'overdue' : 'upcoming',
                    'outstanding_principal' => $loan->outstanding_principal,
                    'accrued_interest' => $this->calculateAccruedInterest($loan, $today),
                ];
            })
            ->values();

        return response()->json([
            'today' => $today->toDateString(),
            'within_days' => $withinDays,
            'rows' => $rows,
        ]);
    }

    private function calculateAccruedInterest(GoldLoan $loan, Carbon $asOf): float
    {
        if ($loan->interest_rate_monthly <= 0 || $loan->outstanding_principal <= 0) {
            return 0;
        }

        $lastDate = $loan->last_interest_date ? Carbon::parse($loan->last_interest_date) : Carbon::parse($loan->disbursed_date);
        if ($asOf->lessThanOrEqualTo($lastDate)) {
            return 0;
        }

        $days = $lastDate->diffInDays($asOf);
        $dailyRate = $loan->interest_rate_monthly / 100 / 30;

        return round($loan->outstanding_principal * $dailyRate * $days, 2);
    }

    private function postDisbursementJournal(GoldLoan $loan): JournalEntry
    {
        $receivable = Account::find($loan->loan_receivable_account_id);
        $cashBank = Account::find($loan->disbursed_from_account_id);

        if (!$receivable || !$cashBank) {
            throw new \RuntimeException('Required accounts not found for loan disbursement.');
        }

        $entry = JournalEntry::create([
            'entry_number'   => $this->nextEntryNumber(),
            'entry_date'     => $loan->disbursed_date,
            'description'    => "Gold loan disbursed {$loan->loan_number}",
            'reference_type' => 'GoldLoan',
            'reference_id'   => $loan->id,
            'branch_id'      => $loan->branch_id,
            'created_by'     => auth()->id(),
            'status'         => 'posted',
        ]);

        JournalEntryLine::create([
            'journal_entry_id' => $entry->id,
            'account_id'       => $receivable->id,
            'debit'            => $loan->loan_amount,
            'credit'           => 0,
            'description'      => 'Gold loan principal receivable',
        ]);

        JournalEntryLine::create([
            'journal_entry_id' => $entry->id,
            'account_id'       => $cashBank->id,
            'debit'            => 0,
            'credit'           => $loan->loan_amount,
            'description'      => 'Cash/Bank disbursed for loan',
        ]);

        return $entry;
    }

    private function postRepaymentJournal(GoldLoan $loan, GoldLoanRepayment $repayment): JournalEntry
    {
        $receivedTo = Account::find($repayment->received_to_account_id);
        $receivable = Account::find($loan->loan_receivable_account_id);
        $interestIncome = Account::find($repayment->interest_income_account_id);

        if (!$receivedTo || !$receivable || !$interestIncome) {
            throw new \RuntimeException('Required accounts not found for repayment posting.');
        }

        $entry = JournalEntry::create([
            'entry_number'   => $this->nextEntryNumber(),
            'entry_date'     => $repayment->payment_date,
            'description'    => "Gold loan repayment {$repayment->payment_number}",
            'reference_type' => 'GoldLoanRepayment',
            'reference_id'   => $repayment->id,
            'branch_id'      => $repayment->branch_id,
            'created_by'     => auth()->id(),
            'status'         => 'posted',
        ]);

        JournalEntryLine::create([
            'journal_entry_id' => $entry->id,
            'account_id'       => $receivedTo->id,
            'debit'            => $repayment->amount,
            'credit'           => 0,
            'description'      => 'Cash/Bank received from customer',
        ]);

        if ($repayment->principal_component > 0) {
            JournalEntryLine::create([
                'journal_entry_id' => $entry->id,
                'account_id'       => $receivable->id,
                'debit'            => 0,
                'credit'           => $repayment->principal_component,
                'description'      => 'Gold loan principal recovered',
            ]);
        }

        if ($repayment->interest_component > 0) {
            JournalEntryLine::create([
                'journal_entry_id' => $entry->id,
                'account_id'       => $interestIncome->id,
                'debit'            => 0,
                'credit'           => $repayment->interest_component,
                'description'      => 'Gold loan interest income',
            ]);
        }

        return $entry;
    }

    private function nextEntryNumber(): string
    {
        $seq = JournalEntry::whereDate('created_at', today())->withTrashed()->count() + 1;
        return 'JE-' . date('Ymd') . '-' . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }
}
