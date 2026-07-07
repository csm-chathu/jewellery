<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\Product;
use App\Models\StockWriteOff;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockWriteOffController extends Controller
{
    public function store(Request $request, Product $product)
    {
        $data = $request->validate([
            'quantity'          => 'required|integer|min:1|max:' . $product->stock_quantity,
            'reason'            => 'required|in:damaged,lost,stolen,other',
            'notes'             => 'nullable|string|max:1000',
            'estimated_value'   => 'required|numeric|min:0',
            'debit_account_id'  => 'required|exists:accounts,id',
            'credit_account_id' => 'required|exists:accounts,id',
        ]);

        DB::transaction(function () use ($data, $product, $request) {
            // 1. Decrement stock
            $product->decrement('stock_quantity', $data['quantity']);

            // 2. Build journal entry number
            $seq    = JournalEntry::whereDate('created_at', today())->withTrashed()->count() + 1;
            $number = 'WO-' . date('Ymd') . '-' . str_pad($seq, 4, '0', STR_PAD_LEFT);

            // 3. Post GL journal
            $entry = JournalEntry::create([
                'entry_number'   => $number,
                'entry_date'     => now()->toDateString(),
                'description'    => "Stock write-off: {$product->name} × {$data['quantity']} ({$data['reason']})",
                'reference_type' => 'StockWriteOff',
                'reference_id'   => null,
                'branch_id'      => $request->user()->branch_id ?? null,
                'created_by'     => $request->user()->id,
                'status'         => 'posted',
            ]);

            // DR — Loss / Expense account
            JournalEntryLine::create([
                'journal_entry_id' => $entry->id,
                'account_id'       => $data['debit_account_id'],
                'debit'            => $data['estimated_value'],
                'credit'           => 0,
                'description'      => "Write-off loss — {$product->name}",
            ]);

            // CR — Inventory / Asset account
            JournalEntryLine::create([
                'journal_entry_id' => $entry->id,
                'account_id'       => $data['credit_account_id'],
                'debit'            => 0,
                'credit'           => $data['estimated_value'],
                'description'      => "Stock reduced — {$product->name} × {$data['quantity']}",
            ]);

            // 4. Save write-off record
            StockWriteOff::create([
                ...$data,
                'product_id'       => $product->id,
                'journal_entry_id' => $entry->id,
                'written_off_by'   => $request->user()->id,
                'branch_id'        => $request->user()->branch_id ?? null,
            ]);
        });

        return response()->json(['message' => 'Write-off recorded successfully.'], 201);
    }
}
