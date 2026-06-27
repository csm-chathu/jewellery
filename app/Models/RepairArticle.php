<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RepairArticle extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'bill_number', 'received_date', 'give_date',
        'article', 'damage', 'customer_name', 'telephone',
        'weight', 'add_weight', 'advance', 'price',
        'done', 'given', 'notes', 'branch_id', 'created_by',
    ];

    protected $casts = [
        'received_date' => 'date',
        'give_date'     => 'date',
        'weight'        => 'float',
        'add_weight'    => 'float',
        'advance'       => 'float',
        'price'         => 'float',
        'done'          => 'boolean',
        'given'         => 'boolean',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
