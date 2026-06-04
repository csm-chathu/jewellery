<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GLController extends Controller
{
    private function dateRange(Request $request): array
    {
        return [
            $request->from ?? '2025-01-01',
            $request->to   ?? date('Y-m-d'),
        ];
    }

    /** Trial Balance: all accounts with debit/credit totals and net balance */
    public function trialBalance(Request $request)
    {
        [$from, $to] = $this->dateRange($request);

        $rows = DB::table('journal_entry_lines as l')
            ->join('accounts as a',       'a.id',  '=', 'l.account_id')
            ->join('journal_entries as je','je.id', '=', 'l.journal_entry_id')
            ->where('je.status', 'posted')
            ->whereBetween('je.entry_date', [$from, $to])
            ->whereNull('je.deleted_at')
            ->whereNull('a.deleted_at')
            ->groupBy('a.id', 'a.code', 'a.name', 'a.type', 'a.sub_type')
            ->select(
                'a.id', 'a.code', 'a.name', 'a.type', 'a.sub_type',
                DB::raw('SUM(l.debit)  as total_debit'),
                DB::raw('SUM(l.credit) as total_credit')
            )
            ->orderBy('a.code')
            ->get()
            ->map(function ($row) {
                $balance = in_array($row->type, ['asset', 'expense'])
                    ? $row->total_debit - $row->total_credit
                    : $row->total_credit - $row->total_debit;

                return array_merge((array) $row, ['balance' => round($balance, 2)]);
            });

        return response()->json([
            'from'   => $from,
            'to'     => $to,
            'rows'   => $rows,
            'totals' => [
                'total_debit'  => round($rows->sum('total_debit'),  2),
                'total_credit' => round($rows->sum('total_credit'), 2),
            ],
        ]);
    }

    /** Account Ledger: all transactions for a single account with running balance */
    public function ledger(Request $request, Account $account)
    {
        [$from, $to] = $this->dateRange($request);

        $lines = DB::table('journal_entry_lines as l')
            ->join('journal_entries as je', 'je.id', '=', 'l.journal_entry_id')
            ->where('l.account_id', $account->id)
            ->where('je.status', 'posted')
            ->whereBetween('je.entry_date', [$from, $to])
            ->whereNull('je.deleted_at')
            ->select(
                'je.id as entry_id', 'je.entry_number', 'je.entry_date',
                'je.description as entry_description', 'je.reference_type',
                'l.debit', 'l.credit', 'l.description as line_description'
            )
            ->orderBy('je.entry_date')
            ->orderBy('je.id')
            ->get();

        $normalDebit    = in_array($account->type, ['asset', 'expense']);
        $runningBalance = 0;

        $lines = $lines->map(function ($line) use (&$runningBalance, $normalDebit) {
            $runningBalance += $normalDebit
                ? ($line->debit - $line->credit)
                : ($line->credit - $line->debit);

            return array_merge((array) $line, ['running_balance' => round($runningBalance, 2)]);
        });

        return response()->json([
            'account'         => $account,
            'from'            => $from,
            'to'              => $to,
            'lines'           => $lines,
            'closing_balance' => round($runningBalance, 2),
        ]);
    }

    /** Balance Sheet: Assets vs Liabilities + Equity as of a date */
    public function balanceSheet(Request $request)
    {
        $asOf = $request->as_of ?? date('Y-m-d');

        $rows = DB::table('journal_entry_lines as l')
            ->join('accounts as a',       'a.id',  '=', 'l.account_id')
            ->join('journal_entries as je','je.id', '=', 'l.journal_entry_id')
            ->where('je.status', 'posted')
            ->where('je.entry_date', '<=', $asOf)
            ->whereIn('a.type', ['asset', 'liability', 'equity'])
            ->whereNull('je.deleted_at')
            ->whereNull('a.deleted_at')
            ->groupBy('a.id', 'a.code', 'a.name', 'a.type', 'a.sub_type')
            ->select(
                'a.id', 'a.code', 'a.name', 'a.type', 'a.sub_type',
                DB::raw('SUM(l.debit)  as total_debit'),
                DB::raw('SUM(l.credit) as total_credit')
            )
            ->orderBy('a.code')
            ->get()
            ->map(function ($row) {
                $balance = $row->type === 'asset'
                    ? $row->total_debit  - $row->total_credit
                    : $row->total_credit - $row->total_debit;

                return array_merge((array) $row, ['balance' => round($balance, 2)]);
            });

        $assets      = $rows->where('type', 'asset')->values();
        $liabilities = $rows->where('type', 'liability')->values();
        $equity      = $rows->where('type', 'equity')->values();

        return response()->json([
            'as_of'       => $asOf,
            'assets'      => $assets,
            'liabilities' => $liabilities,
            'equity'      => $equity,
            'totals'      => [
                'total_assets'      => round($assets->sum('balance'),      2),
                'total_liabilities' => round($liabilities->sum('balance'), 2),
                'total_equity'      => round($equity->sum('balance'),      2),
            ],
        ]);
    }

    /** Income Statement: Revenues vs Expenses for a period */
    public function incomeStatement(Request $request)
    {
        [$from, $to] = $this->dateRange($request);

        $rows = DB::table('journal_entry_lines as l')
            ->join('accounts as a',       'a.id',  '=', 'l.account_id')
            ->join('journal_entries as je','je.id', '=', 'l.journal_entry_id')
            ->where('je.status', 'posted')
            ->whereBetween('je.entry_date', [$from, $to])
            ->whereIn('a.type', ['revenue', 'expense'])
            ->whereNull('je.deleted_at')
            ->whereNull('a.deleted_at')
            ->groupBy('a.id', 'a.code', 'a.name', 'a.type', 'a.sub_type')
            ->select(
                'a.id', 'a.code', 'a.name', 'a.type', 'a.sub_type',
                DB::raw('SUM(l.debit)  as total_debit'),
                DB::raw('SUM(l.credit) as total_credit')
            )
            ->orderBy('a.code')
            ->get()
            ->map(function ($row) {
                $balance = $row->type === 'expense'
                    ? $row->total_debit  - $row->total_credit
                    : $row->total_credit - $row->total_debit;

                return array_merge((array) $row, ['balance' => round($balance, 2)]);
            });

        $revenues  = $rows->where('type', 'revenue')->values();
        $expenses  = $rows->where('type', 'expense')->values();
        $netIncome = round($revenues->sum('balance') - $expenses->sum('balance'), 2);

        return response()->json([
            'from'           => $from,
            'to'             => $to,
            'revenues'       => $revenues,
            'expenses'       => $expenses,
            'total_revenue'  => round($revenues->sum('balance'), 2),
            'total_expenses' => round($expenses->sum('balance'), 2),
            'net_income'     => $netIncome,
        ]);
    }
}
