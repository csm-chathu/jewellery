<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BusinessLoan extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'loan_number', 'lender_name', 'principal_amount', 'outstanding_balance',
        'interest_rate', 'monthly_installment', 'start_date', 'due_date',
        'liability_account_id', 'received_to_account_id', 'journal_entry_id',
        'status', 'notes', 'branch_id', 'user_id',
    ];

    protected $casts = [
        'principal_amount' => 'float',
        'outstanding_balance' => 'float',
        'interest_rate' => 'float',
        'monthly_installment' => 'float',
        'start_date' => 'date',
        'due_date' => 'date',
    ];

    public function repayments()
    {
        return $this->hasMany(LoanRepayment::class, 'loan_id');
    }

    public function liabilityAccount()
    {
        return $this->belongsTo(Account::class, 'liability_account_id');
    }

    public function receivedToAccount()
    {
        return $this->belongsTo(Account::class, 'received_to_account_id');
    }

    public function journalEntry()
    {
        return $this->belongsTo(JournalEntry::class);
    }
}
