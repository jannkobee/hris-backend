<?php

namespace App\Services\Payroll;

use App\Models\StatutoryRule;
use App\Tenancy\TenantContext;
use Carbon\CarbonInterface;

class StatutoryRuleResolver
{
    public function __construct(private readonly TenantContext $tenantContext)
    {
    }

    public function effectiveFor(CarbonInterface|string $date): ?StatutoryRule
    {
        $date = (string) $date;
        $countryCode = strtoupper((string) ($this->tenantContext->organization()->country_code ?: 'PH'));

        return StatutoryRule::query()
            ->where('country_code', $countryCode)
            ->whereDate('effective_from', '<=', $date)
            ->where(function ($query) use ($date): void {
                $query->whereNull('effective_until')->orWhereDate('effective_until', '>=', $date);
            })
            ->orderByDesc('effective_from')
            ->first();
    }
}
