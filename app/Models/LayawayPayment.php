<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LayawayPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'layaway_id', 'receipt_number', 'amount', 'payment_method',
        'payment_date', 'notes', 'sms_sent', 'created_by',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'amount'       => 'float',
        'sms_sent'     => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (empty($model->receipt_number)) {
                $model->receipt_number = 'LPR-' . strtoupper(substr(uniqid(), -6));
            }
        });
    }

    public function layaway()
    {
        return $this->belongsTo(Layaway::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
