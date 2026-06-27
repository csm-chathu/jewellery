<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CashBalanceEntry extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'entry_date', 'entry_time', 'jewellery_cash', 'old_cash',
        'actual_cash', 'shots', 'notes', 'branch_id', 'created_by',
    ];

    protected $casts = [
        'entry_date'     => 'date:Y-m-d',
        'jewellery_cash' => 'float',
        'old_cash'       => 'float',
        'actual_cash'    => 'float',
        'shots'          => 'array',
    ];
}
