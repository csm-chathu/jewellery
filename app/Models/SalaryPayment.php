<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalaryPayment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'payment_number', 'employee_id', 'branch_id', 'user_id', 'journal_entry_id',
        'period_from', 'period_to', 'payment_date',
        'basic_salary', 'allowances', 'deductions', 'net_salary',
        'payment_method', 'paid_from_account_id', 'status', 'notes',
    ];

    protected $casts = [
        'period_from'   => 'date',
        'period_to'     => 'date',
        'payment_date'  => 'date',
        'basic_salary'  => 'float',
        'allowances'    => 'float',
        'deductions'    => 'float',
        'net_salary'    => 'float',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function paidBy()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function journalEntry()
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function paidFromAccount()
    {
        return $this->belongsTo(Account::class, 'paid_from_account_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}
