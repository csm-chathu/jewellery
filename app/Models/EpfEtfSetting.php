<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EpfEtfSetting extends Model
{
    protected $fillable = [
        'epf_employee_rate', 'epf_employer_rate', 'etf_employer_rate',
        'effective_from', 'is_active', 'notes', 'created_by',
    ];

    protected $casts = [
        'epf_employee_rate' => 'float',
        'epf_employer_rate' => 'float',
        'etf_employer_rate' => 'float',
        'effective_from'    => 'date',
        'is_active'         => 'boolean',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** Returns the currently active rate row, or a default object if none set. */
    public static function current(): self
    {
        return static::where('is_active', true)
            ->orderByDesc('effective_from')
            ->orderByDesc('id')
            ->firstOrNew([], [
                'epf_employee_rate' => 8.00,
                'epf_employer_rate' => 12.00,
                'etf_employer_rate' => 3.00,
            ]);
    }
}
