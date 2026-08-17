<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PrivateGoldLoan extends Model
{
    protected $fillable = [
        'loan_number', 'borrower_name', 'borrower_nic', 'borrower_phone',
        'pledge_description', 'declared_karat', 'gross_weight', 'net_weight',
        'loan_amount', 'outstanding_principal', 'interest_rate_monthly',
        'disbursed_date', 'maturity_date', 'status', 'notes',
        'branch_id', 'recorded_by',
    ];

    protected $casts = [
        'gross_weight'           => 'float',
        'net_weight'             => 'float',
        'loan_amount'            => 'float',
        'outstanding_principal'  => 'float',
        'interest_rate_monthly'  => 'float',
        'disbursed_date'         => 'date',
        'maturity_date'          => 'date',
    ];

    public function repayments()
    {
        return $this->hasMany(PrivateGoldLoanRepayment::class);
    }

    public function recorder()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    protected static function nextLoanNumber(): string
    {
        $prefix = 'PGL-' . now()->format('Y') . '-';
        $last   = static::where('loan_number', 'like', $prefix . '%')
                        ->orderByDesc('loan_number')->value('loan_number');
        $seq    = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;
        return $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }

    public static function generateNumber(): string
    {
        return static::nextLoanNumber();
    }
}
