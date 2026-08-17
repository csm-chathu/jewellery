<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PrivateGoldLoanRepayment extends Model
{
    protected $fillable = [
        'private_gold_loan_id', 'payment_date',
        'principal_paid', 'interest_paid', 'total_paid',
        'notes', 'recorded_by',
    ];

    protected $casts = [
        'principal_paid' => 'float',
        'interest_paid'  => 'float',
        'total_paid'     => 'float',
        'payment_date'   => 'date',
    ];

    public function loan()
    {
        return $this->belongsTo(PrivateGoldLoan::class, 'private_gold_loan_id');
    }
}
