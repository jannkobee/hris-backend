<?php

namespace App\Services\AppSettings;

use App\Models\AppSetting;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;

class AppSettingService
{
    public function all(): array
    {
        $values = collect(config('app_settings', []))
            ->mapWithKeys(fn (array $definition, string $key) => [$key => $definition['default']])
            ->all();

        if (! Schema::hasTable('app_settings')) {
            return $values;
        }

        AppSetting::query()->get()->each(function (AppSetting $setting) use (&$values): void {
            if (array_key_exists($setting->key, config('app_settings', []))) {
                $values[$setting->key] = $this->decode($setting->key, $setting->value);
            }
        });

        return $values;
    }

    public function get(string $key, mixed $fallback = null): mixed
    {
        $definitions = config('app_settings', []);

        if (! array_key_exists($key, $definitions)) {
            return $fallback;
        }

        if (! Schema::hasTable('app_settings')) {
            return $definitions[$key]['default'] ?? $fallback;
        }

        $setting = AppSetting::query()->where('key', $key)->first();

        return $setting
            ? $this->decode($key, $setting->value)
            : ($definitions[$key]['default'] ?? $fallback);
    }

    public function definitions(): array
    {
        return collect(config('app_settings', []))
            ->map(fn (array $definition) => collect($definition)->except('rules')->all())
            ->all();
    }

    public function update(array $values): array
    {
        $definitions = config('app_settings', []);

        foreach ($values as $key => $value) {
            if (! array_key_exists($key, $definitions)) {
                throw new InvalidArgumentException("Unknown app setting: {$key}");
            }

            AppSetting::query()->updateOrCreate(
                ['key' => $key],
                ['value' => $this->encode($value)]
            );
        }

        return $this->all();
    }

    private function encode(mixed $value): string
    {
        return json_encode($value, JSON_THROW_ON_ERROR);
    }

    private function decode(string $key, ?string $value): mixed
    {
        if ($value === null) {
            return config('app_settings', [])[$key]['default'] ?? null;
        }

        try {
            return json_decode($value, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return config('app_settings', [])[$key]['default'] ?? null;
        }
    }
}
