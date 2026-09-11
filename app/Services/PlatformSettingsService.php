<?php

namespace App\Services;

use App\Models\PlatformSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class PlatformSettingsService
{
    private const CACHE_PREFIX = 'platform_settings:';

    private const CACHE_TTL_SECONDS = 3600;

    public function get(string $key, mixed $default = null): mixed
    {
        $settings = $this->all();

        return array_key_exists($key, $settings) ? $settings[$key] : $default;
    }

    public function getBool(string $key, bool $default = false): bool
    {
        return (bool) $this->get($key, $default);
    }

    public function getInt(string $key, int $default = 0): int
    {
        return (int) $this->get($key, $default);
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return Cache::remember(
            self::CACHE_PREFIX.'all',
            self::CACHE_TTL_SECONDS,
            function () {
                if (! Schema::hasTable('platform_settings')) {
                    return [];
                }

                return PlatformSetting::query()
                    ->get(['key', 'value', 'type'])
                    ->mapWithKeys(fn (PlatformSetting $setting) => [
                        $setting->key => $this->castValue($setting->value, $setting->type),
                    ])
                    ->all();
            },
        );
    }

    public function set(string $key, mixed $value, string $type = 'string', ?string $group = null): PlatformSetting
    {
        [$storedValue, $resolvedType] = $this->prepareStoredValue($value, $type);

        $setting = PlatformSetting::query()->updateOrCreate(
            ['key' => $key],
            [
                'value' => $storedValue,
                'type' => $resolvedType,
                'group' => $group,
            ],
        );

        $this->flushCache();

        return $setting;
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    public function setMany(array $settings): void
    {
        foreach ($settings as $key => $value) {
            $this->set($key, $value);
        }
    }

    public function forget(string $key): bool
    {
        $deleted = PlatformSetting::query()->where('key', $key)->delete() > 0;

        if ($deleted) {
            $this->flushCache();
        }

        return $deleted;
    }

    public function flushCache(): void
    {
        Cache::forget(self::CACHE_PREFIX.'all');
    }

    public function defaultCurrency(): string
    {
        return strtoupper((string) $this->get('default_currency', 'TTD'));
    }

    /**
     * @return list<string>
     */
    public function allowedCurrencies(): array
    {
        $configured = $this->get('allowed_currencies', ['TTD', 'USD', 'GBP']);

        if (! is_array($configured)) {
            $configured = ['TTD', 'USD', 'GBP'];
        }

        $currencies = array_values(array_unique(array_map(
            fn ($code) => strtoupper((string) $code),
            $configured,
        )));

        if (! in_array('TTD', $currencies, true)) {
            array_unshift($currencies, 'TTD');
        }

        usort($currencies, fn (string $a, string $b) => $a === 'TTD' ? -1 : ($b === 'TTD' ? 1 : strcmp($a, $b)));

        return $currencies;
    }

    public function formatMoney(float $amount, ?string $currency = null): string
    {
        $currency = strtoupper($currency ?? $this->defaultCurrency());

        return sprintf('%s $%s', $currency, number_format($amount, 2));
    }

    private function castValue(?string $value, string $type): mixed
    {
        if ($value === null) {
            return null;
        }

        return match ($type) {
            'boolean', 'bool' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'integer', 'int' => (int) $value,
            'float', 'double' => (float) $value,
            'json', 'array' => json_decode($value, true),
            default => $value,
        };
    }

    /**
     * @return array{0: string|null, 1: string}
     */
    private function prepareStoredValue(mixed $value, string $type): array
    {
        if (is_array($value)) {
            return [json_encode($value, JSON_THROW_ON_ERROR), 'json'];
        }

        if (is_bool($value)) {
            return [$value ? '1' : '0', 'boolean'];
        }

        if (is_int($value) || is_float($value)) {
            return [(string) $value, is_int($value) ? 'integer' : 'float'];
        }

        return [(string) $value, $type];
    }
}
