<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\AuditLog;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AccountTransferController extends Controller
{
    public function index(Request $request)
    {
        $transfers = JournalEntry::with('createdBy:id,name', 'lines.account:id,code,name')
            ->where('reference_type', 'account_transfer')
            ->when($request->from, fn($q, $v) => $q->where('entry_date', '>=', $v))
            ->when($request->to,   fn($q, $v) => $q->where('entry_date', '<=', $v))
            ->orderByDesc('entry_date')
            ->orderByDesc('id')
            ->paginate(20);

        return response()->json($transfers);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'from_account_id' => 'required|exists:accounts,id',
            'to_account_id'   => 'required|different:from_account_id|exists:accounts,id',
            'amount'          => 'required|numeric|min:0.01',
            'entry_date'      => 'required|date',
            'description'     => 'nullable|string|max:255',
        ]);

        $from = Account::findOrFail($data['from_account_id']);
        $to   = Account::findOrFail($data['to_account_id']);

        DB::beginTransaction();
        try {
            $seq         = JournalEntry::whereDate('created_at', today())->withTrashed()->count() + 1;
            $entryNumber = 'JE-' . date('Ymd') . '-' . str_pad($seq, 4, '0', STR_PAD_LEFT);

            $note  = $data['description'] ?? "Transfer from {$from->name} to {$to->name}";

            $entry = JournalEntry::create([
                'entry_number'   => $entryNumber,
                'entry_date'     => $data['entry_date'],
                'description'    => $note,
                'reference_type' => 'account_transfer',
                'branch_id'      => auth()->user()->branch_id,
                'created_by'     => auth()->id(),
                'status'         => 'posted',
            ]);

            // DR the destination account (money going in)
            JournalEntryLine::create([
                'journal_entry_id' => $entry->id,
                'account_id'       => $to->id,
                'debit'            => $data['amount'],
                'credit'           => 0,
                'description'      => $note,
            ]);

            // CR the source account (money going out)
            JournalEntryLine::create([
                'journal_entry_id' => $entry->id,
                'account_id'       => $from->id,
                'debit'            => 0,
                'credit'           => $data['amount'],
                'description'      => $note,
            ]);

            AuditLog::record('account_transfer', "Account transfer {$entryNumber}: {$from->name} → {$to->name} ({$data['amount']})", $entry);

            DB::commit();
            return response()->json($entry->load('lines.account:id,code,name'), 201);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to save transfer.'], 500);
        }
    }

    public function destroy(JournalEntry $journalEntry)
    {
        if ($journalEntry->reference_type !== 'account_transfer') {
            return response()->json(['message' => 'Not a transfer entry.'], 422);
        }

        AuditLog::record('account_transfer_deleted', "Account transfer {$journalEntry->entry_number} deleted", $journalEntry);
        $journalEntry->delete();

        return response()->json(['message' => 'Transfer deleted.']);
    }
}
