<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SlArticleSale extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'sale_date', 'article', 'weight', 'gold_rate',
        'by_price', 'sale_price', 'profit', 'notes',
        'branch_id', 'created_by',
    ];

    protected $casts = [
        'sale_date'  => 'date',
        'weight'     => 'float',
        'gold_rate'  => 'float',
        'by_price'   => 'float',
        'sale_price' => 'float',
        'profit'     => 'float',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $m) {
            if ($m->weight && $m->gold_rate) {
                $m->by_price = round($m->gold_rate / 8 * $m->weight, 2);
            }
        });
    }

    public function branch()  { return $this->belongsTo(Branch::class); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
}
