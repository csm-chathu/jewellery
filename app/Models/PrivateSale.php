<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PrivateSale extends Model
{
    protected $fillable = [
        'reference_number', 'sale_date', 'buyer_name', 'description', 'item_type',
        'gross_weight', 'net_weight', 'declared_karat',
        'rate_per_gram', 'total_amount', 'payment_method', 'notes',
        'recorded_by', 'branch_id',
    ];

    protected $casts = [
        'sale_date'    => 'date',
        'gross_weight' => 'float',
        'net_weight'   => 'float',
        'rate_per_gram'=> 'float',
        'total_amount' => 'float',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (empty($model->reference_number)) {
                $model->reference_number = 'IGS-' . strtoupper(substr(uniqid(), -6));
            }
        });
    }

    public function recorder()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}
