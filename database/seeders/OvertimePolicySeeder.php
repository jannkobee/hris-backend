<?php

namespace Database\Seeders;

use App\Models\OvertimePolicy;
use Illuminate\Database\Seeder;

class OvertimePolicySeeder extends Seeder
{
    public function run(): void
    {
        $policies = [
            [
                'day_type' => 'regular_day',
                'multiplier' => 1.25,
                'is_active' => true,
            ],
            [
                'day_type' => 'rest_day',
                'multiplier' => 1.30,
                'is_active' => true,
            ],
            [
                'day_type' => 'special_non_working_day',
                'multiplier' => 1.30,
                'is_active' => true,
            ],
            [
                'day_type' => 'regular_holiday',
                'multiplier' => 2.00,
                'is_active' => true,
            ],
            [
                'day_type' => 'special_working_day',
                'multiplier' => 1.00,
                'is_active' => true,
            ],
            [
                'day_type' => 'company_holiday',
                'multiplier' => 1.30,
                'is_active' => true,
            ],
        ];

        foreach ($policies as $policy) {
            OvertimePolicy::firstOrCreate(
                ['day_type' => $policy['day_type']],
                $policy
            );
        }
    }
}
