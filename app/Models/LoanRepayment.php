<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LoanRepayment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'payment_number', 'loan_id', 'payment_date', 'principal_amount', 'interest_amount',
        'total_amount', 'paid_from_account_id', 'interest_expense_account_id',
        'journal_entry_id', 'notes', 'branch_id', 'user_id',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'principal_amount' => 'float',
        'interest_amount' => 'float',
        'total_amount' => 'float',
    ];

    public function loan()
    {
        return $this->belongsTo(BusinessLoan::class, 'loan_id');
    }

    public function paidFromAccount()
    {
        return $this->belongsTo(Account::class, 'paid_from_account_id');
    }

    public function interestExpenseAccount()
    {
        return $this->belongsTo(Account::class, 'interest_expense_account_id');
    }

    public function journalEntry()
    {
        return $this->belongsTo(JournalEntry::class);
    }
}
