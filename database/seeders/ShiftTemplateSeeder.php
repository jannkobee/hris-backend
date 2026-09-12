<?php

namespace Database\Seeders;

use App\Models\ShiftTemplate;
use Illuminate\Database\Seeder;

class ShiftTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'code' => 'DAY-8-5',
                'name' => 'Regular Day Shift (8:00 AM - 5:00 PM)',
                'start_time' => '08:00:00',
                'end_time' => '17:00:00',
                'break_minutes' => 60,
                'grace_minutes' => 15,
                'days_of_week' => [1, 2, 3, 4, 5],
                'is_active' => true,
            ],
            [
                'code' => 'EARLY-7-4',
                'name' => 'Morning Early Shift (7:00 AM - 4:00 PM)',
                'start_time' => '07:00:00',
                'end_time' => '16:00:00',
                'break_minutes' => 60,
                'grace_minutes' => 15,
                'days_of_week' => [1, 2, 3, 4, 5],
                'is_active' => true,
            ],
            [
                'code' => 'MID-1-10',
                'name' => 'Mid Shift (1:00 PM - 10:00 PM)',
                'start_time' => '13:00:00',
                'end_time' => '22:00:00',
                'break_minutes' => 60,
                'grace_minutes' => 15,
                'days_of_week' => [1, 2, 3, 4, 5],
                'is_active' => true,
            ],
            [
                'code' => 'NIGHT-10-7',
                'name' => 'Night / Graveyard Shift (10:00 PM - 7:00 AM)',
                'start_time' => '22:00:00',
                'end_time' => '07:00:00',
                'break_minutes' => 60,
                'grace_minutes' => 15,
                'days_of_week' => [1, 2, 3, 4, 5],
                'is_active' => true,
            ],
            [
                'code' => 'FLEX-9-6',
                'name' => 'Flexible Shift (9:00 AM - 6:00 PM)',
                'start_time' => '09:00:00',
                'end_time' => '18:00:00',
                'break_minutes' => 60,
                'grace_minutes' => 60,
                'days_of_week' => [1, 2, 3, 4, 5],
                'is_active' => true,
            ],
        ];

        foreach ($templates as $template) {
            ShiftTemplate::firstOrCreate(
                ['code' => $template['code']],
                $template
            );
        }
    }
}
