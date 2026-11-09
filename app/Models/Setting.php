<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * Key/value runtime settings editable from Admin > Settings.
 * Secrets (payment keys, DB password) are NOT stored here; they live in .env.
 */
class Setting extends Model
{
    public const CACHE_KEY = 'arsh.settings';

    protected $fillable = ['key', 'value', 'group', 'type'];

    protected static function booted(): void
    {
        static::saved(fn () => static::flush());
        static::deleted(fn () => static::flush());
    }

    public static function flush(): void
    {
        try {
            Cache::forget(self::CACHE_KEY);
        } catch (Throwable) {
            // cache not available yet (e.g. during first migration)
        }
    }

    /** @return array<string, string|null> raw stored values */
    protected static function stored(): array
    {
        try {
            return Cache::rememberForever(self::CACHE_KEY, fn () => static::query()->pluck('value', 'key')->all());
        } catch (Throwable) {
            return []; // database not migrated yet: fall back to defaults
        }
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $definition = config("arsh.settings.$key"); // [group, type, default]
        $stored = static::stored();

        if (array_key_exists($key, $stored) && $stored[$key] !== null) {
            return static::cast($stored[$key], $definition[1] ?? 'string');
        }

        if ($definition) {
            return static::cast($definition[2], $definition[1]);
        }

        return $default;
    }

    public static function set(string $key, mixed $value): void
    {
        $definition = config("arsh.settings.$key");

        static::query()->updateOrCreate(
            ['key' => $key],
            [
                'value' => is_bool($value) ? ($value ? '1' : '0') : (string) $value,
                'group' => $definition[0] ?? 'general',
                'type' => $definition[1] ?? 'string',
            ]
        );
    }

    protected static function cast(mixed $value, string $type): mixed
    {
        return match ($type) {
            'bool' => in_array((string) $value, ['1', 'true', 'on', 'yes'], true),
            'int' => (int) $value,
            'decimal' => (string) ($value === null || $value === '' ? '0' : $value),
            default => $value === null ? '' : (string) $value,
        };
    }
}
