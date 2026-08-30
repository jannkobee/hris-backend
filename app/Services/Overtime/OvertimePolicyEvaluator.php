<?php

namespace App\Services\Overtime;

use App\Models\Holiday;
use App\Models\OvertimePolicy;
use Carbon\Carbon;

class OvertimePolicyEvaluator
{
    public function evaluate(string $date, float $hours): array
    {
        $day = Carbon::parse($date);
        $holiday = Holiday::query()->whereDate('date', $day)->first();
        $type = $holiday ? $holiday->type : ($day->isWeekend() ? 'rest_day' : 'regular_day');
        $multiplier = (float) (OvertimePolicy::query()->where('day_type', $type)->where('is_active', true)->value('multiplier') ?? 1);

        return ['day_type' => $type, 'premium_multiplier' => $multiplier, 'premium_hours' => round($hours * $multiplier, 2)];
    }
}
