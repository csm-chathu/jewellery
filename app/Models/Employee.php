<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'employee_number', 'branch_id', 'name', 'nic', 'designation', 'department',
        'employment_type', 'basic_salary', 'joined_date', 'terminated_date',
        'contact_phone', 'contact_email', 'address',
        'bank_name', 'bank_account', 'is_active', 'notes',
    ];

    protected $casts = [
        'basic_salary'     => 'float',
        'joined_date'      => 'date',
        'terminated_date'  => 'date',
        'is_active'        => 'boolean',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function salaryPayments()
    {
        return $this->hasMany(SalaryPayment::class);
    }
}
