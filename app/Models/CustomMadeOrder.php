<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomMadeOrder extends Model
{
    protected $fillable = [
        'reference_number', 'customer_id', 'customer_name',
        'description', 'drawing_image_url',
        'estimated_weight', 'karat', 'gold_rate_per_gram',
        'estimated_gold_cost', 'making_charge', 'other_charges', 'estimated_total',
        'advance_amount', 'advance_paid_at',
        'final_weight', 'final_gold_cost', 'final_making_charge',
        'final_other_charges', 'final_total', 'balance_amount',
        'status', 'expected_at', 'completed_at', 'issued_at',
        'notes', 'created_by', 'branch_id',
    ];

    protected $casts = [
        'estimated_weight'   => 'float',
        'gold_rate_per_gram' => 'float',
        'estimated_gold_cost'=> 'float',
        'making_charge'      => 'float',
        'other_charges'      => 'float',
        'estimated_total'    => 'float',
        'advance_amount'     => 'float',
        'final_weight'       => 'float',
        'final_gold_cost'    => 'float',
        'final_making_charge'=> 'float',
        'final_other_charges'=> 'float',
        'final_total'        => 'float',
        'balance_amount'     => 'float',
        'advance_paid_at'    => 'date',
        'expected_at'        => 'date',
        'completed_at'       => 'date',
        'issued_at'          => 'date',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (empty($model->reference_number)) {
                $model->reference_number = 'CMO-' . strtoupper(substr(uniqid(), -7));
            }
        });

        static::saving(function (self $model) {
            $model->estimated_total = round(
                ($model->estimated_gold_cost ?? 0) +
                ($model->making_charge ?? 0) +
                ($model->other_charges ?? 0),
                2
            );
        });
    }
}
