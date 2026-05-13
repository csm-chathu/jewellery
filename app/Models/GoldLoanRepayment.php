<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GoldLoanRepayment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'payment_number', 'gold_loan_id', 'payment_date', 'amount',
        'interest_component', 'principal_component', 'received_to_account_id',
        'interest_income_account_id', 'journal_entry_id', 'notes',
        'branch_id', 'user_id',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'amount' => 'float',
        'interest_component' => 'float',
        'principal_component' => 'float',
    ];

    public function goldLoan()
    {
        return $this->belongsTo(GoldLoan::class);
    }

    public function receivedToAccount()
    {
        return $this->belongsTo(Account::class, 'received_to_account_id');
    }

    public function interestIncomeAccount()
    {
        return $this->belongsTo(Account::class, 'interest_income_account_id');
    }

    public function journalEntry()
    {
        return $this->belongsTo(JournalEntry::class);
    }
}
