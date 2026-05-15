<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\AuditLog;
use App\Models\EpfEtfSetting;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\SalaryPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalaryPaymentController extends Controller
{
    public function index(Request $request)
    {
        return SalaryPayment::with(['employee:id,employee_number,name,designation', 'paidBy:id,name', 'paidFromAccount:id,code,name'])
            ->when($request->employee_id, fn($q, $v) => $q->where('employee_id', $v))
            ->when($request->from,        fn($q, $v) => $q->where('payment_date', '>=', $v))
            ->when($request->to,          fn($q, $v) => $q->where('payment_date', '<=', $v))
            ->when($request->status,      fn($q, $v) => $q->where('status', $v))
            ->orderByDesc('payment_date')
            ->orderByDesc('id')
            ->paginate(20);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'employee_id'          => 'required|exists:employees,id',
            'period_from'          => 'required|date',
            'period_to'            => 'required|date|after_or_equal:period_from',
            'payment_date'         => 'required|date',
            'basic_salary'         => 'required|numeric|min:0',
            'allowances'           => 'nullable|numeric|min:0',
            'deductions'           => 'nullable|numeric|min:0',
            'apply_epf_etf'        => 'nullable|boolean',
            'payment_method'       => 'required|in:cash,bank_transfer,cheque',
            'paid_from_account_id' => 'required|exists:accounts,id',
            'status'               => 'nullable|in:draft,paid',
            'notes'                => 'nullable|string|max:500',
        ]);

        $data['allowances'] = $data['allowances'] ?? 0;
        $data['deductions'] = $data['deductions'] ?? 0;

        // EPF / ETF calculation
        $epfEtfSetting   = EpfEtfSetting::current();
        $applyEpfEtf     = $data['apply_epf_etf'] ?? false;
        $grossSalary     = $data['basic_salary'] + $data['allowances'];
        $epfEmployee     = 0;
        $epfEmployer     = 0;
        $etfEmployer     = 0;

        if ($applyEpfEtf && $epfEtfSetting->exists) {
            $epfEmployee = round($grossSalary * $epfEtfSetting->epf_employee_rate / 100, 2);
            $epfEmployer = round($grossSalary * $epfEtfSetting->epf_employer_rate / 100, 2);
            $etfEmployer = round($grossSalary * $epfEtfSetting->etf_employer_rate / 100, 2);
        }

        $data['gross_salary']     = $grossSalary;
        $data['epf_employee']     = $epfEmployee;
        $data['epf_employer']     = $epfEmployer;
        $data['etf_employer']     = $etfEmployer;
        $data['epf_etf_setting_id'] = ($applyEpfEtf && $epfEtfSetting->exists) ? $epfEtfSetting->id : null;
        $data['net_salary']       = $grossSalary - $epfEmployee - $data['deductions'];

        if ($data['net_salary'] <= 0) {
            return response()->json(['message' => 'Net salary must be greater than zero.'], 422);
        }

        DB::beginTransaction();
        try {
            $seq    = SalaryPayment::withTrashed()->count() + 1;
            $payNum = 'SAL-' . date('Ym') . '-' . str_pad($seq, 4, '0', STR_PAD_LEFT);

            $payment = SalaryPayment::create(array_merge($data, [
                'payment_number' => $payNum,
                'branch_id'      => auth()->user()->branch_id,
                'user_id'        => auth()->id(),
                'status'         => $data['status'] ?? 'paid',
            ]));

            // ── Auto-post GL Journal Entry if status = paid ──────────────
            if ($payment->status === 'paid') {
                $this->postJournalEntry($payment);
            }

            AuditLog::record('salary_paid', "Salary {$payNum} paid – LKR {$payment->net_salary}", $payment);

            DB::commit();
            return response()->json($payment->load('employee:id,employee_number,name', 'journalEntry', 'paidFromAccount:id,code,name'), 201);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to process salary payment: ' . $e->getMessage()], 500);
        }
    }

    public function destroy(SalaryPayment $salaryPayment)
    {
        if ($salaryPayment->status === 'paid') {
            return response()->json(['message' => 'Posted salary payments cannot be deleted. Contact admin.'], 422);
        }

        AuditLog::record('salary_deleted', "Salary payment {$salaryPayment->payment_number} deleted", $salaryPayment);
        $salaryPayment->delete();

        return response()->json(['message' => 'Payment deleted.']);
    }

    /** Summary totals per employee for a date range — used for payroll report */
    public function summary(Request $request)
    {
        $from = $request->from ?? date('Y-01-01');
        $to   = $request->to   ?? date('Y-m-d');

        $rows = DB::table('salary_payments as sp')
            ->join('employees as e', 'e.id', '=', 'sp.employee_id')
            ->whereNull('sp.deleted_at')
            ->where('sp.status', 'paid')
            ->whereBetween('sp.payment_date', [$from, $to])
            ->groupBy('e.id', 'e.employee_number', 'e.name', 'e.designation', 'e.department')
            ->select(
                'e.id', 'e.employee_number', 'e.name', 'e.designation', 'e.department',
                DB::raw('COUNT(sp.id) as payment_count'),
                DB::raw('SUM(sp.basic_salary)  as total_basic'),
                DB::raw('SUM(sp.allowances)    as total_allowances'),
                DB::raw('SUM(sp.deductions)    as total_deductions'),
                DB::raw('SUM(sp.epf_employee)  as total_epf_employee'),
                DB::raw('SUM(sp.epf_employer)  as total_epf_employer'),
                DB::raw('SUM(sp.etf_employer)  as total_etf_employer'),
                DB::raw('SUM(sp.net_salary)    as total_net')
            )
            ->orderBy('e.name')
            ->get();

        return response()->json([
            'from'          => $from,
            'to'            => $to,
            'rows'          => $rows,
            'grand_total'   => round($rows->sum('total_net'), 2),
        ]);
    }

    // ── Private ─────────────────────────────────────────────────────────────

    private function postJournalEntry(SalaryPayment $payment): void
    {
        // Find accounts by code (seeded in migration 000021)
        $salaryExpenseAccount = Account::where('code', '5210')->first();
        $cashOrBankAccount    = Account::find($payment->paid_from_account_id);

        if (!$salaryExpenseAccount || !$cashOrBankAccount) {
            throw new \RuntimeException('Required accounts (5210, paid-from) not found. Cannot post journal entry.');
        }

        $employee = $payment->employee ?? $payment->load('employee')->employee;
        $desc     = "Salary – {$employee->name} ({$payment->payment_number}) {$payment->period_from->format('M Y')}";

        $seq         = JournalEntry::whereDate('created_at', today())->withTrashed()->count() + 1;
        $entryNumber = 'JE-' . date('Ymd') . '-' . str_pad($seq, 4, '0', STR_PAD_LEFT);

        $entry = JournalEntry::create([
            'entry_number'   => $entryNumber,
            'entry_date'     => $payment->payment_date,
            'description'    => $desc,
            'reference_type' => 'SalaryPayment',
            'reference_id'   => $payment->id,
            'branch_id'      => $payment->branch_id,
            'created_by'     => auth()->id(),
            'status'         => 'posted',
        ]);

        // DR Salaries & Wages (expense)
        JournalEntryLine::create([
            'journal_entry_id' => $entry->id,
            'account_id'       => $salaryExpenseAccount->id,
            'debit'            => $payment->net_salary,
            'credit'           => 0,
            'description'      => $desc,
        ]);

        // CR Cash / Bank (asset)
        JournalEntryLine::create([
            'journal_entry_id' => $entry->id,
            'account_id'       => $cashOrBankAccount->id,
            'debit'            => 0,
            'credit'           => $payment->net_salary,
            'description'      => $desc,
        ]);

        $payment->update(['journal_entry_id' => $entry->id]);
    }
}
