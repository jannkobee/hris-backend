<?php

namespace App\Services\Plans;

use App\Models\PlatformOperationLog;
use App\Models\PlatformSetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PlatformPricingService
{
    private const KEY = 'billing.pricing.ph';

    public function current(): array
    {
        $effective = collect($this->history())->first(fn ($item) => \Carbon\Carbon::parse($item['effective_at'])->lte(now()));
        if ($effective) {
            return array_merge(['minimum_billable_employees' => 0], $effective);
        }

        return array_merge([
            'country_code' => 'PH', 'currency' => 'php',
            'free_employee_limit' => 10, 'growth_price_per_employee' => 1900,
            'minimum_billable_employees' => 0,
        ], PlatformSetting::query()->where('key', self::KEY)->value('value') ?? []);
    }

    public function update(array $values): array
    {
        return DB::transaction(function () use ($values) {
            $pricing = array_merge($this->current(), $values, ['country_code' => 'PH', 'version' => (string) Str::uuid(),
                'effective_at' => isset($values['effective_at']) ? \Carbon\Carbon::parse($values['effective_at'])->toIso8601String() : now()->toIso8601String()]);
            PlatformSetting::create(['key' => self::KEY.'.'.$pricing['version'], 'value' => $pricing]);
            PlatformOperationLog::create(['action' => 'platform pricing updated', 'payload' => $pricing, 'occurred_at' => now()]);

            return $pricing;
        });
    }

    public function history(): array
    {
        return PlatformSetting::where('key', 'like', self::KEY.'.%')->latest()->get()->pluck('value')
            ->sortByDesc('effective_at')->values()->all();
    }
}
