<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Sale extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'branch_id', 'invoice_number', 'customer_id', 'user_id', 'subtotal',
        'discount', 'tax', 'gold_value_total', 'gemstone_value_total',
        'making_charges_total', 'wastage_total', 'tax_rate', 'total',
        'payment_method', 'payment_status', 'sale_type', 'delivery_status',
        'booking_expires_at', 'delivered_at', 'amount_paid', 'notes',
        'journal_entry_id', 'sold_at',
    ];

    protected $casts = [
        'sold_at'    => 'datetime',
        'booking_expires_at' => 'date',
        'delivered_at' => 'datetime',
        'subtotal'   => 'float',
        'discount'   => 'float',
        'tax'        => 'float',
        'gold_value_total' => 'float',
        'gemstone_value_total' => 'float',
        'making_charges_total' => 'float',
        'wastage_total' => 'float',
        'tax_rate'    => 'float',
        'total'      => 'float',
        'amount_paid'=> 'float',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(SaleItem::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function journalEntry()
    {
        return $this->belongsTo(JournalEntry::class);
    }
}
