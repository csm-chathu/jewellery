<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GoldBalanceEntry extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'entry_date', 'description', 'karat',
        'give_weight_1', 'give_weight_2', 'give_weight_3', 'give_weight_4',
        'article', 'weight', 'wastage', 'other_charge', 'total_gold',
        'branch_id', 'created_by',
    ];

    protected $casts = [
        'entry_date'    => 'date',
        'give_weight_1' => 'float',
        'give_weight_2' => 'float',
        'give_weight_3' => 'float',
        'give_weight_4' => 'float',
        'weight'        => 'float',
        'wastage'       => 'float',
        'other_charge'  => 'float',
        'total_gold'    => 'float',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $m) {
            $give = ($m->give_weight_1 ?? 0)
                  + ($m->give_weight_2 ?? 0)
                  + ($m->give_weight_3 ?? 0)
                  + ($m->give_weight_4 ?? 0);
            $m->total_gold = round($give - ($m->weight ?? 0) - ($m->wastage ?? 0), 3);
        });
    }

    public function branch()  { return $this->belongsTo(Branch::class); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
}
