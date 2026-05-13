<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GoldLoan extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'loan_number', 'customer_id', 'pledge_description', 'item_type',
        'gross_weight', 'deduction_weight', 'net_weight', 'declared_karat',
        'loan_amount', 'interest_rate_monthly', 'disbursed_date', 'maturity_date',
        'outstanding_principal', 'last_interest_date', 'loan_receivable_account_id',
        'disbursed_from_account_id', 'journal_entry_id', 'status', 'notes',
        'branch_id', 'user_id',
    ];

    protected $casts = [
        'gross_weight' => 'float',
        'deduction_weight' => 'float',
        'net_weight' => 'float',
        'loan_amount' => 'float',
        'interest_rate_monthly' => 'float',
        'outstanding_principal' => 'float',
        'disbursed_date' => 'date',
        'maturity_date' => 'date',
        'last_interest_date' => 'date',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function repayments()
    {
        return $this->hasMany(GoldLoanRepayment::class);
    }

    public function loanReceivableAccount()
    {
        return $this->belongsTo(Account::class, 'loan_receivable_account_id');
    }

    public function disbursedFromAccount()
    {
        return $this->belongsTo(Account::class, 'disbursed_from_account_id');
    }

    public function journalEntry()
    {
        return $this->belongsTo(JournalEntry::class);
    }
}
