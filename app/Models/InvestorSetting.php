<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['key', 'value'])]
class InvestorSetting extends Model
{
    protected static array $cache = [];

    public static function get(string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, self::$cache)) {
            return self::$cache[$key] ?? $default;
        }

        $setting = self::query()->where('key', $key)->first();
        self::$cache[$key] = $setting?->value;

        return $setting?->value ?? $default;
    }

    public static function set(string $key, ?string $value): void
    {
        self::query()->updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );
        self::$cache[$key] = $value;
    }

    public static function clearCache(): void
    {
        self::$cache = [];
    }
}
