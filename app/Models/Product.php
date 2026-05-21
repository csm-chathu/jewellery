<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'branch_id', 'sku', 'barcode', 'name', 'description', 'category_id', 'material',
        'weight', 'karat', 'making_charge_type', 'making_charge', 'wastage_percent',
        'size', 'color', 'gemstone', 'gemstone_weight', 'gemstone_value', 'gemstone_quality',
        'purchase_price', 'stock_quantity',
        'min_stock_level', 'image', 'image_public_id', 'is_active', 'supplier_id',
    ];

    protected $casts = [
        'is_active'      => 'boolean',
        'weight'         => 'float',
        'purchase_price' => 'float',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function saleItems()
    {
        return $this->hasMany(SaleItem::class);
    }

    public function purchaseItems()
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function getLowStockAttribute(): bool
    {
        return $this->stock_quantity <= $this->min_stock_level;
    }
}
