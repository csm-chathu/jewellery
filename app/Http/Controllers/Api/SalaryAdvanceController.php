<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\AuditLog;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\SalaryAdvance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalaryAdvanceController extends Controller
{
    public function index(Request $request)
    {
        return SalaryAdvance::with(['employee:id,employee_number,name', 'givenBy:id,name'])
            ->when($request->employee_id, fn($q, $v) => $q->where('employee_id', $v))
            ->when($request->status,      fn($q, $v) => $q->where('status', $v))
            ->orderByDesc('given_date')
            ->orderByDesc('id')
            ->paginate(25);
    }

    /** Pending advances for a specific employee — used in the salary payment modal */
    public function pending($employeeId)
    {
        $advances = SalaryAdvance::where('employee_id', $employeeId)
            ->where('status', 'pending')
            ->orderBy('given_date')
            ->get(['id', 'advance_number', 'amount', 'given_date', 'reason']);

        return response()->json($advances);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'employee_id'          => 'required|exists:employees,id',
            'amount'               => 'required|numeric|min:1',
            'given_date'           => 'required|date',
            'paid_from_account_id' => 'required|exists:accounts,id',
            'reason'               => 'nullable|string|max:500',
            'notes'                => 'nullable|string|max:500',
        ]);

        DB::beginTransaction();
        try {
            $seq    = SalaryAdvance::withTrashed()->count() + 1;
            $advNum = 'ADV-' . date('Ym') . '-' . str_pad($seq, 4, '0', STR_PAD_LEFT);

            $advance = SalaryAdvance::create([
                'advance_number' => $advNum,
                'employee_id'    => $data['employee_id'],
                'branch_id'      => auth()->user()->branch_id,
                'user_id'        => auth()->id(),
                'amount'         => $data['amount'],
                'given_date'     => $data['given_date'],
                'reason'         => $data['reason'] ?? null,
                'notes'          => $data['notes'] ?? null,
                'status'         => 'pending',
            ]);

            // GL: DR Salary Advance (1300), CR Cash/Bank
            $advanceAccount  = Account::where('code', '1300')->first();
            $cashBankAccount = Account::find($data['paid_from_account_id']);

            if (!$advanceAccount || !$cashBankAccount) {
                throw new \RuntimeException('Account 1300 (Salary Advance) not found. Run migrations first.');
            }

            $employee = $advance->load('employee')->employee;
            $desc     = "Salary Advance – {$employee->name} ({$advNum})";

            $seq2        = JournalEntry::whereDate('created_at', today())->withTrashed()->count() + 1;
            $entryNumber = 'JE-' . date('Ymd') . '-' . str_pad($seq2, 4, '0', STR_PAD_LEFT);

            $entry = JournalEntry::create([
                'entry_number'   => $entryNumber,
                'entry_date'     => $advance->given_date,
                'description'    => $desc,
                'reference_type' => 'SalaryAdvance',
                'reference_id'   => $advance->id,
                'branch_id'      => $advance->branch_id,
                'created_by'     => auth()->id(),
                'status'         => 'posted',
            ]);

            JournalEntryLine::create([
                'journal_entry_id' => $entry->id,
                'account_id'       => $advanceAccount->id,
                'debit'            => $advance->amount,
                'credit'           => 0,
                'description'      => $desc,
            ]);

            JournalEntryLine::create([
                'journal_entry_id' => $entry->id,
                'account_id'       => $cashBankAccount->id,
                'debit'            => 0,
                'credit'           => $advance->amount,
                'description'      => $desc,
            ]);

            $advance->update(['journal_entry_id' => $entry->id]);

            AuditLog::record('advance_given', "Advance {$advNum} – LKR {$advance->amount} to {$employee->name}", $advance);

            DB::commit();
            return response()->json($advance->load('employee:id,employee_number,name', 'givenBy:id,name'), 201);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to record advance: ' . $e->getMessage()], 500);
        }
    }

    public function destroy(SalaryAdvance $salaryAdvance)
    {
        if ($salaryAdvance->status !== 'pending') {
            return response()->json(['message' => 'Only pending advances can be cancelled.'], 422);
        }

        AuditLog::record('advance_cancelled', "Advance {$salaryAdvance->advance_number} cancelled", $salaryAdvance);
        $salaryAdvance->update(['status' => 'cancelled']);
        $salaryAdvance->delete();

        return response()->json(['message' => 'Advance cancelled.']);
    }
}
