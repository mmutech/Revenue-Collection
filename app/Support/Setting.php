<?php

namespace App\Support;

use App\Models\SystemSetting;

final class Setting
{
    /** @var array<string, mixed>|null */
    private static ?array $cache = null;

    public static function get(string $key, mixed $default = null): mixed
    {
        $settings = self::all();

        return array_key_exists($key, $settings) ? $settings[$key] : $default;
    }

    public static function bool(string $key, bool $default = false): bool
    {
        $value = self::get($key, $default);

        return filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) ?? $default;
    }

    public static function int(string $key, int $default = 0): int
    {
        $value = self::get($key, $default);

        return is_numeric($value) ? (int) $value : $default;
    }

    public static function string(string $key, string $default = ''): string
    {
        $value = self::get($key, $default);

        return is_scalar($value) ? (string) $value : $default;
    }

    /**
     * @return array<string, mixed>
     */
    public static function all(): array
    {
        if (self::$cache !== null) {
            return self::$cache;
        }

        try {
            self::$cache = SystemSetting::query()
                ->get()
                ->mapWithKeys(fn (SystemSetting $setting) => [$setting->key => $setting->typedValue()])
                ->all();
        } catch (\Throwable) {
            self::$cache = [];
        }

        return self::$cache;
    }

    public static function set(string $key, mixed $value, ?int $userId = null): SystemSetting
    {
        $setting = SystemSetting::query()->firstOrNew(['key' => $key]);
        $setting->fill([
            'value' => is_array($value) ? json_encode($value) : (string) $value,
            'group' => $setting->group ?: 'general',
            'label' => $setting->label ?: str($key)->afterLast('.')->headline()->toString(),
            'type' => $setting->type ?: (is_bool($value) ? 'boolean' : (is_numeric($value) ? 'number' : 'string')),
        ]);
        if ($userId) {
            $setting->updated_by = $userId;
        }
        $setting->save();

        self::flush();

        return $setting;
    }

    public static function flush(): void
    {
        self::$cache = null;
    }
}
