<?php

namespace Tests\Unit;

use App\Models\Holiday;
use PHPUnit\Framework\TestCase;

class HolidayTest extends TestCase
{
    public function test_it_defines_supported_workforce_calendar_day_types(): void
    {
        $this->assertSame([
            'regular_holiday',
            'special_non_working_day',
            'special_working_day',
            'company_holiday',
        ], Holiday::TYPES);
    }

    public function test_calendar_fields_are_mass_assignable(): void
    {
        $holiday = new Holiday([
            'name' => 'Company Foundation Day',
            'date' => '2026-09-01',
            'type' => 'company_holiday',
            'description' => 'Company-wide non-working day.',
        ]);

        $this->assertSame('Company Foundation Day', $holiday->name);
        $this->assertSame('company_holiday', $holiday->type);
        $this->assertSame('2026-09-01', $holiday->date->format('Y-m-d'));
    }
}
