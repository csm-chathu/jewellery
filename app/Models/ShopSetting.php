<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShopSetting extends Model
{
    use HasFactory;

    protected $fillable = ['key', 'value'];

    public static function getValue(string $key, $default = null): ?string
    {
        return static::where('key', $key)->value('value') ?? $default;
    }

    public static function setValue(string $key, $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
    }

    public static function allAsObject(): array
    {
        return static::all()->pluck('value', 'key')->toArray();
    }
}
