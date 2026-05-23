<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalaryAdvance extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'advance_number', 'employee_id', 'branch_id', 'user_id',
        'amount', 'given_date', 'reason', 'status',
        'salary_payment_id', 'journal_entry_id', 'notes',
    ];

    protected $casts = [
        'given_date' => 'date',
        'amount'     => 'float',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function givenBy()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function journalEntry()
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function salaryPayment()
    {
        return $this->belongsTo(SalaryPayment::class);
    }
}
