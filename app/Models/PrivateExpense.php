<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PrivateExpense extends Model
{
    protected $fillable = [
        'reference_number', 'expense_date', 'category', 'description',
        'amount', 'payment_method', 'notes', 'recorded_by', 'branch_id',
    ];

    protected $casts = [
        'expense_date' => 'date',
        'amount'       => 'float',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (empty($model->reference_number)) {
                $model->reference_number = 'IGE-' . strtoupper(substr(uniqid(), -6));
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
