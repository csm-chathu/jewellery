<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GoldLoanLedger extends Model
{
    use SoftDeletes;

    protected $table = 'gold_loan_ledger';

    protected $fillable = [
        'entry_date', 'loan_rate', 'loan_amount', 'weight',
        'description', 'give_weight', 'sort_order', 'branch_id', 'created_by',
    ];

    protected $casts = [
        'entry_date'  => 'date',
        'loan_rate'   => 'float',
        'loan_amount' => 'float',
        'weight'      => 'float',
        'give_weight' => 'float',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $m) {
            // weight = loan_amount / loan_rate * 8
            if ($m->loan_rate && $m->loan_amount) {
                $m->weight = round($m->loan_amount / $m->loan_rate * 8, 3);
            }
        });
    }

    public function branch()  { return $this->belongsTo(Branch::class); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
}
