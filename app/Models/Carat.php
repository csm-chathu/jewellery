<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Carat extends Model
{
    protected $fillable = ['label', 'purity', 'sort_order', 'is_active'];

    protected $casts = [
        'purity'    => 'float',
        'is_active' => 'boolean',
    ];

    public function goldRates()
    {
        return $this->hasMany(GoldRate::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }
}
