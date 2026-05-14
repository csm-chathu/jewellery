<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GoldRate extends Model
{
    protected $fillable = ['date', 'carat_id', 'rate_per_gram', 'created_by'];

    protected $casts = [
        'date'          => 'date',
        'rate_per_gram' => 'float',
    ];

    public function carat()
    {
        return $this->belongsTo(Carat::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** Fallback purity ratios when a karat has no stored rate */
    private static array $purityFallback = [
        '9k'  => 9  / 24,
        '14k' => 14 / 24,
        '18k' => 18 / 24,
        '22k' => 22 / 24,
        '24k' => 24 / 24,
    ];

    /** All rates for today, keyed by carat_id */
    public static function todayRates(): \Illuminate\Support\Collection
    {
        return static::with('carat')
            ->where('date', today()->toDateString())
            ->get()
            ->keyBy('carat_id');
    }

    /** Single rate for today for a specific carat */
    public static function todayForCarat(int $caratId): ?self
    {
        return static::where('date', today()->toDateString())
            ->where('carat_id', $caratId)
            ->first();
    }

    /**
     * Today's rates keyed by lowercase label: ['24k' => self, '22k' => self, ...]
     * Used by controllers that need to look up rate by karat string.
     */
    public static function todayRatesByLabel(): array
    {
        return static::with('carat')
            ->where('date', today()->toDateString())
            ->get()
            ->mapWithKeys(fn ($r) => [strtolower($r->carat?->label ?? '') => $r])
            ->all();
    }

    /** Today's rate for a karat label, e.g. '22k' or '22K' */
    public static function todayForKaratLabel(string $label): ?self
    {
        $carat = Carat::where('label', strtoupper($label))->first();
        if (!$carat) return null;
        return static::todayForCarat($carat->id);
    }

    /** Purity for a karat label, checking DB first then hardcoded fallback */
    public static function purityForLabel(string $label): float
    {
        $carat = Carat::where('label', strtoupper($label))->first();
        if ($carat) return (float) $carat->purity;
        return self::$purityFallback[strtolower($label)] ?? 1.0;
    }

    /** @deprecated Use todayRates() */
    public static function today(): ?self
    {
        return static::where('date', today()->toDateString())->first();
    }

    /** Calculate LKR value using this rate's carat purity */
    public function calculate(float $weightGrams): float
    {
        $purity = $this->carat?->purity ?? 1.0;
        return round($this->rate_per_gram * $weightGrams, 2);
    }

    /** Calculate using explicit purity (for backward compat) */
    public function calculateWithPurity(float $weightGrams, float $purity): float
    {
        return round($this->rate_per_gram * $weightGrams * $purity, 2);
    }
}
