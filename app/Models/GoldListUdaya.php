<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GoldListUdaya extends Model
{
    use SoftDeletes;

    protected $table = 'gold_list_udaya';

    protected $fillable = [
        'item', 'description', 'weight', 'stock_weight',
        'price', 'rate', 'average_karat', 'moose_pay',
        'branch_id', 'created_by',
    ];

    protected $casts = [
        'weight'       => 'float',
        'stock_weight' => 'float',
        'price'        => 'float',
        'rate'         => 'float',
        'moose_pay'    => 'float',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $m) {
            if ($m->weight && $m->price) {
                $m->rate = round($m->price / $m->weight * 8, 4);
            }
        });
    }

    public function branch()  { return $this->belongsTo(Branch::class); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
}
