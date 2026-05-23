<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaleItem extends Model
{
    protected $fillable = [
        'sale_id', 'product_id', 'quantity', 'unit_price', 'display_price', 'discount', 'total',
        'gold_value', 'gemstone_value', 'making_charge', 'wastage_amount',
    ];

    protected $casts = [
        'unit_price'    => 'float',
        'display_price' => 'float',
        'discount'   => 'float',
        'total'      => 'float',
        'gold_value' => 'float',
        'gemstone_value' => 'float',
        'making_charge' => 'float',
        'wastage_amount' => 'float',
    ];

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
