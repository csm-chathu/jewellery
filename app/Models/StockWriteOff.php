<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StockWriteOff extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'product_id', 'quantity', 'reason', 'notes',
        'estimated_value', 'debit_account_id', 'credit_account_id',
        'journal_entry_id', 'written_off_by', 'branch_id',
    ];

    protected $casts = [
        'estimated_value' => 'float',
    ];

    public function product()      { return $this->belongsTo(Product::class); }
    public function debitAccount() { return $this->belongsTo(Account::class, 'debit_account_id'); }
    public function creditAccount(){ return $this->belongsTo(Account::class, 'credit_account_id'); }
    public function journalEntry() { return $this->belongsTo(JournalEntry::class); }
}
