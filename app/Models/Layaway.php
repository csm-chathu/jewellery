<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Layaway extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'reference_number', 'customer_id', 'product_id', 'item_description',
        'total_amount', 'paid_amount', 'balance_amount', 'status',
        'booking_date', 'expected_by', 'notes', 'branch_id', 'created_by',
        'sale_id', 'collected_at',
    ];

    protected $casts = [
        'booking_date'  => 'date',
        'expected_by'   => 'date',
        'collected_at'  => 'datetime',
        'total_amount'  => 'float',
        'paid_amount'   => 'float',
        'balance_amount' => 'float',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (empty($model->reference_number)) {
                $model->reference_number = 'LAY-' . strtoupper(substr(uniqid(), -6));
            }
        });
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function payments()
    {
        return $this->hasMany(LayawayPayment::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}
