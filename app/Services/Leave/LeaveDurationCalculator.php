<?php

namespace App\Services\Leave;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Validation\ValidationException;

class LeaveDurationCalculator
{
    /**
     * Returns chargeable weekdays grouped by calendar year. A holiday calendar
     * can be added here later without changing the leave workflow.
     */
    public function daysByYear(string|Carbon $startDate, string|Carbon $endDate): array
    {
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->startOfDay();
        $days = [];

        foreach (CarbonPeriod::create($start, $end) as $date) {
            if (! $date->isWeekend()) {
                $days[$date->year] = ($days[$date->year] ?? 0) + 1;
            }
        }

        if ($days === []) {
            throw ValidationException::withMessages([
                'start_date' => 'Leave must include at least one working day.',
            ]);
        }

        return $days;
    }
}
