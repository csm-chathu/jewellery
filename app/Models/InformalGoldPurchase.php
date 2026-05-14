<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InformalGoldPurchase extends Model
{
    protected $fillable = [
        'reference_number', 'purchase_date', 'description', 'item_type',
        'gross_weight', 'deduction_weight', 'net_weight', 'declared_karat',
        'rate_per_gram', 'final_price', 'payment_method', 'notes',
        'recorded_by', 'branch_id',
    ];

    protected $casts = [
        'purchase_date'    => 'date',
        'gross_weight'     => 'float',
        'deduction_weight' => 'float',
        'net_weight'       => 'float',
        'rate_per_gram'    => 'float',
        'final_price'      => 'float',
    ];

    public function recorder()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (empty($model->reference_number)) {
                $model->reference_number = 'IGP-' . strtoupper(substr(uniqid(), -6));
            }
        });
    }
}
