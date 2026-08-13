<?php

namespace Tests\Unit;

use App\Services\Leave\LeaveDurationCalculator;
use Tests\TestCase;

class LeaveDurationCalculatorTest extends TestCase
{
    public function test_it_counts_weekdays_and_splits_a_request_across_years(): void
    {
        $days = app(LeaveDurationCalculator::class)->daysByYear('2026-12-31', '2027-01-04');

        $this->assertSame([2026 => 1, 2027 => 2], $days);
    }
}
