<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PrivateCashAdjustment extends Model
{
    protected $fillable = [
        'reference_number', 'adjustment_date', 'type', 'description',
        'amount', 'payment_method', 'notes', 'recorded_by', 'branch_id',
    ];

    protected $casts = [
        'adjustment_date' => 'date',
        'amount'          => 'float',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (empty($model->reference_number)) {
                $model->reference_number = 'ADJ-' . strtoupper(substr(uniqid(), -6));
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
