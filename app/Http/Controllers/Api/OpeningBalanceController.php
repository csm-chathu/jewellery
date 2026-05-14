<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OpeningBalanceController extends Controller
{
    /** Return all balance-sheet accounts with their current opening balance (if set). */
    public function index()
    {
        $accounts = Account::whereIn('type', ['asset', 'liability', 'equity'])
            ->where('is_active', true)
            ->orderBy('code')
            ->get(['id', 'code', 'name', 'type', 'sub_type']);

        // Find the existing opening balance journal entry (if any)
        $entry = JournalEntry::where('reference_type', 'opening_balance')->first();

        $balances = [];
        if ($entry) {
            foreach ($entry->lines as $line) {
                // Return whichever side is non-zero as the "amount"
                $balances[$line->account_id] = $line->debit > 0 ? $line->debit : $line->credit;
            }
        }

        return response()->json([
            'accounts'   => $accounts,
            'balances'   => $balances,
            'entry_date' => $entry?->entry_date?->format('Y-m-d'),
        ]);
    }

    /** Save (or replace) the opening balance journal entry. */
    public function store(Request $request)
    {
        $data = $request->validate([
            'date'                  => 'required|date',
            'balances'              => 'required|array',
            'balances.*.account_id' => 'required|exists:accounts,id',
            'balances.*.amount'     => 'required|numeric|min:0',
        ]);

        $lines = collect($data['balances'])->filter(fn($b) => $b['amount'] > 0);

        if ($lines->isEmpty()) {
            return response()->json(['message' => 'Enter at least one non-zero balance.'], 422);
        }

        // Load account types so we know debit vs credit
        $accountIds = $lines->pluck('account_id');
        $accounts   = Account::whereIn('id', $accountIds)->get()->keyBy('id');

        $totalDebits  = 0;
        $totalCredits = 0;
        $journalLines = [];

        foreach ($lines as $b) {
            $acct = $accounts[$b['account_id']];
            $isDebit = in_array($acct->type, ['asset', 'expense']);
            $amount  = round((float) $b['amount'], 2);

            $journalLines[] = [
                'account_id'  => $acct->id,
                'debit'       => $isDebit ? $amount : 0,
                'credit'      => $isDebit ? 0 : $amount,
                'description' => 'Opening balance',
            ];

            if ($isDebit) $totalDebits  += $amount;
            else          $totalCredits += $amount;
        }

        // Auto-balance difference into Owner's Capital (code 3000)
        $diff = round($totalDebits - $totalCredits, 2);
        if (abs($diff) > 0.01) {
            $capital = Account::where('code', '3000')->first();
            if ($capital) {
                $journalLines[] = [
                    'account_id'  => $capital->id,
                    'debit'       => $diff < 0 ? abs($diff) : 0,
                    'credit'      => $diff > 0 ? $diff : 0,
                    'description' => 'Opening balance equity (auto-balanced)',
                ];
            }
        }

        DB::transaction(function () use ($data, $journalLines) {
            // Remove previous opening balance entry
            $old = JournalEntry::where('reference_type', 'opening_balance')->first();
            if ($old) {
                $old->lines()->delete();
                $old->forceDelete();
            }

            $seq = JournalEntry::whereDate('created_at', today())->withTrashed()->count() + 1;
            $entry = JournalEntry::create([
                'entry_number'   => 'OB-' . date('Ymd') . '-' . str_pad($seq, 4, '0', STR_PAD_LEFT),
                'entry_date'     => $data['date'],
                'description'    => 'Opening Balances',
                'reference_type' => 'opening_balance',
                'branch_id'      => auth()->user()->branch_id,
                'created_by'     => auth()->id(),
                'status'         => 'posted',
            ]);

            foreach ($journalLines as $line) {
                JournalEntryLine::create(array_merge($line, ['journal_entry_id' => $entry->id]));
            }
        });

        return response()->json(['message' => 'Opening balances saved.']);
    }
}
