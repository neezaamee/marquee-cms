<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupportSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'description',
    ];

    /**
     * Get a setting by key with fallback default.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $setting = static::where('key', $key)->first();
        if (!$setting) {
            return $default;
        }

        $decoded = json_decode($setting->value, true);
        return json_last_error() === JSON_ERROR_NONE ? $decoded : $setting->value;
    }

    /**
     * Set a setting value.
     */
    public static function set(string $key, mixed $value, ?string $description = null): self
    {
        $encoded = is_array($value) || is_object($value) ? json_encode($value) : $value;

        return static::updateOrCreate(
            ['key' => $key],
            [
                'value' => $encoded,
                'description' => $description ?? $key,
            ]
        );
    }
}
