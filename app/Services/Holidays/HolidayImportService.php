<?php

namespace App\Services\Holidays;

use App\Models\Holiday;
use App\Tenancy\TenantContext;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class HolidayImportService
{
    public function __construct(private readonly TenantContext $tenantContext)
    {
    }

    public function import(int $year, string $countryCode = null): array
    {
        $countryCode = strtoupper($countryCode ?: $this->tenantContext->organization()?->country_code ?: config('holiday_provider.country_code'));
        try {
            $response = Http::baseUrl(rtrim(config('holiday_provider.base_url'), '/'))->acceptJson()->timeout(config('holiday_provider.timeout_seconds'))->get("PublicHolidays/{$year}/{$countryCode}");
        } catch (ConnectionException) {
            throw ValidationException::withMessages(['provider' => 'The holiday provider could not be reached.']);
        }
        if (! $response->successful() || ! is_array($response->json())) {
            throw ValidationException::withMessages(['provider' => 'The holiday provider returned an invalid holiday response.']);
        }
        $imported = 0;
        $skipped = 0;
        foreach ($response->json() as $holiday) {
            $date = $holiday['date'] ?? null;
            $name = $holiday['localName'] ?? $holiday['name'] ?? null;
            if (! is_string($date) || ! is_string($name) || Holiday::query()->where('date', $date)->exists()) {
                $skipped++;

                continue;
            }
            Holiday::create(['name' => $name, 'date' => $date, 'type' => 'regular_holiday', 'description' => sprintf('Imported from holiday provider (%s).', $countryCode)]);
            $imported++;
        }

        return compact('imported', 'skipped', 'countryCode', 'year');
    }
}
