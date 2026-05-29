<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Expense extends Model
{
    use HasFactory;

    protected $fillable = [
        'expense_date', 'category', 'description', 'amount', 'payment_method',
        'reference_number', 'paid_by_user_id', 'branch_id', 'notes', 'created_by_user_id',
        'expense_account_id', 'paid_from_account_id', 'journal_entry_id',
    ];

    protected $casts = [
        'expense_date' => 'date',
        'amount'       => 'decimal:2',
    ];

    const PAYMENT_METHODS = ['cash', 'cheque', 'bank_transfer', 'card'];

    public function paidByUser()
    {
        return $this->belongsTo(User::class, 'paid_by_user_id');
    }

    public function createdByUser()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function expenseAccount()
    {
        return $this->belongsTo(Account::class, 'expense_account_id');
    }

    public function paidFromAccount()
    {
        return $this->belongsTo(Account::class, 'paid_from_account_id');
    }

    public function journalEntry()
    {
        return $this->belongsTo(JournalEntry::class);
    }
}
