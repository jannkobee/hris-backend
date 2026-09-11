<?php

namespace Tests\Unit;

use App\Services\AppSettings\AppSettingService;
use App\Services\Payroll\PayrollWorkSummaryService;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class NightShiftDifferentialTest extends TestCase
{
    private PayrollWorkSummaryService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $settings = $this->createMock(AppSettingService::class);
        $this->service = new PayrollWorkSummaryService($settings);
    }

    public function test_day_shift_has_zero_night_differential(): void
    {
        $timeIn = Carbon::parse('2026-09-11 08:00:00', 'Asia/Manila');
        $timeOut = Carbon::parse('2026-09-11 17:00:00', 'Asia/Manila');

        $minutes = $this->service->calculateNightDifferentialMinutes($timeIn, $timeOut);
        $this->assertSame(0, $minutes);
    }

    public function test_evening_shift_with_night_overlap(): void
    {
        // 18:00 to 23:30 (22:00 to 23:30 = 90 mins)
        $timeIn = Carbon::parse('2026-09-11 18:00:00', 'Asia/Manila');
        $timeOut = Carbon::parse('2026-09-11 23:30:00', 'Asia/Manila');

        $minutes = $this->service->calculateNightDifferentialMinutes($timeIn, $timeOut);
        $this->assertSame(90, $minutes);
    }

    public function test_overnight_graveyard_shift(): void
    {
        // 22:00 to 06:00 next day = 480 mins (8 hours)
        $timeIn = Carbon::parse('2026-09-11 22:00:00', 'Asia/Manila');
        $timeOut = Carbon::parse('2026-09-12 06:00:00', 'Asia/Manila');

        $minutes = $this->service->calculateNightDifferentialMinutes($timeIn, $timeOut);
        $this->assertSame(480, $minutes);
    }

    public function test_extended_night_shift_caps_at_six_am(): void
    {
        // 20:00 to 08:00 next day (22:00 to 06:00 = 480 mins)
        $timeIn = Carbon::parse('2026-09-11 20:00:00', 'Asia/Manila');
        $timeOut = Carbon::parse('2026-09-12 08:00:00', 'Asia/Manila');

        $minutes = $this->service->calculateNightDifferentialMinutes($timeIn, $timeOut);
        $this->assertSame(480, $minutes);
    }

    public function test_early_morning_shift(): void
    {
        // 04:00 to 12:00 (04:00 to 06:00 = 120 mins)
        $timeIn = Carbon::parse('2026-09-11 04:00:00', 'Asia/Manila');
        $timeOut = Carbon::parse('2026-09-11 12:00:00', 'Asia/Manila');

        $minutes = $this->service->calculateNightDifferentialMinutes($timeIn, $timeOut);
        $this->assertSame(120, $minutes);
    }
}

