<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReworkOrder extends Model
{
    protected $fillable = [
        'reference_number', 'source_type', 'buyback_id', 'scrap_item_id',
        'description', 'goldsmith_name',
        'input_weight', 'input_karat', 'input_gold_cost',
        'added_gold_weight', 'added_gold_cost',
        'making_charge', 'making_charge_notes',
        'total_cost',
        'output_weight', 'output_karat', 'output_product_id',
        'started_at', 'expected_at', 'completed_at',
        'status', 'notes', 'created_by', 'branch_id',
    ];

    protected $casts = [
        'input_weight'      => 'float',
        'input_gold_cost'   => 'float',
        'added_gold_weight' => 'float',
        'added_gold_cost'   => 'float',
        'making_charge'     => 'float',
        'total_cost'        => 'float',
        'output_weight'     => 'float',
        'started_at'        => 'date',
        'expected_at'       => 'date',
        'completed_at'      => 'date',
    ];

    public function buyback()
    {
        return $this->belongsTo(GoldBuyback::class, 'buyback_id');
    }

    public function scrapItem()
    {
        return $this->belongsTo(ScrapItem::class, 'scrap_item_id');
    }

    public function outputProduct()
    {
        return $this->belongsTo(Product::class, 'output_product_id');
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
                $model->reference_number = 'RWK-' . strtoupper(substr(uniqid(), -6));
            }
        });

        static::saving(function (self $model) {
            $model->total_cost = round(
                ($model->input_gold_cost ?? 0) +
                ($model->added_gold_cost ?? 0) +
                ($model->making_charge ?? 0),
                2
            );
        });
    }
}
