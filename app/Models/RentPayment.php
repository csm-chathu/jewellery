<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RentPayment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'rent_number', 'month', 'property_name', 'landlord_name', 'due_date',
        'payment_date', 'amount', 'payment_method', 'cheque_number', 'cheque_date',
        'cheque_bank_name', 'expense_account_id', 'paid_from_account_id',
        'journal_entry_id', 'reminder_days_before', 'last_reminded_at',
        'status', 'notes', 'branch_id', 'user_id',
    ];

    protected $casts = [
        'due_date' => 'date',
        'payment_date' => 'date',
        'cheque_date' => 'date',
        'last_reminded_at' => 'datetime',
        'amount' => 'float',
    ];

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
