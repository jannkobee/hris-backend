<?php

namespace App\Services\AppSettings;

use App\Models\AppSetting;
use App\Tenancy\TenantContext;
use DateTimeZone;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;

class AppSettingService
{
    private const VALUES_CACHE_KEY_PREFIX = 'app-settings.values.v2.';

    private const CACHE_TTL_SECONDS = 300;

    public function __construct(private readonly TenantContext $tenantContext)
    {
    }

    public function all(): array
    {
        if (! $this->tenantContext->hasOrganization()) {
            return $this->defaultValues();
        }

        return Cache::remember(
            $this->cacheKey(),
            self::CACHE_TTL_SECONDS,
            fn (): array => $this->loadValues()
        );
    }

    private function loadValues(): array
    {
        $values = $this->defaultValues();

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

        return $this->all()[$key] ?? ($definitions[$key]['default'] ?? $fallback);
    }

    public function definitions(): array
    {
        return collect(config('app_settings', []))
            ->map(function (array $definition, string $key): array {
                $publicDefinition = collect($definition)->except('rules')->all();

                if ($key === 'organization.timezone') {
                    $publicDefinition['options'] = DateTimeZone::listIdentifiers();
                }

                $publicDefinition['permission'] = $this->permissionFor($key);

                return $publicDefinition;
            })
            ->all();
    }

    public function update(array $values): array
    {
        $this->tenantContext->organization();
        $definitions = config('app_settings', []);

        DB::transaction(function () use ($values, $definitions): void {
            foreach ($values as $key => $value) {
                if (! array_key_exists($key, $definitions)) {
                    throw new InvalidArgumentException("Unknown app setting: {$key}");
                }

                AppSetting::query()->updateOrCreate(
                    ['key' => $key],
                    ['value' => $this->encode($value)]
                );
            }

            if (array_key_exists('organization.company_name', $values)) {
                $this->tenantContext->organization()->update([
                    'name' => $values['organization.company_name'],
                ]);
            }
        });

        Cache::forget($this->cacheKey());

        return $this->all();
    }

    public function permissionFor(string $key): string
    {
        return match (true) {
            str_starts_with($key, 'organization.') => 'manage-organization-settings',
            str_starts_with($key, 'attendance.') => 'manage-attendance-settings',
            str_starts_with($key, 'payroll.') => 'manage-payroll-settings',
            default => 'manage-feature-settings',
        };
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

    private function defaultValues(): array
    {
        return collect(config('app_settings', []))
            ->mapWithKeys(fn (array $definition, string $key) => [$key => $definition['default']])
            ->all();
    }

    private function cacheKey(): string
    {
        return self::VALUES_CACHE_KEY_PREFIX.$this->tenantContext->organization()->getKey();
    }
}
