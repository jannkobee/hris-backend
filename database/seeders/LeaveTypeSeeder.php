<?php

namespace Database\Seeders;

use App\Models\LeaveType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class LeaveTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            [
                'name' => 'Service Incentive Leave (SIL)',
                'default_days' => 5,
                'is_paid' => true,
                'description' => 'Mandatory leave for employees with at least one year of service.'
            ],
            [
                'name' => 'Vacation Leave',
                'default_days' => 10,
                'is_paid' => true,
                'description' => 'Standard company-provided vacation leave.'
            ],
            [
                'name' => 'Sick Leave',
                'default_days' => 10,
                'is_paid' => true,
                'description' => 'Standard company-provided sick leave.'
            ],
            [
                'name' => 'Maternity Leave',
                'default_days' => 105,
                'is_paid' => true,
                'description' => '105 days for female employees (RA 11210).'
            ],
            [
                'name' => 'Paternity Leave',
                'default_days' => 7,
                'is_paid' => true,
                'description' => '7 days for married male employees (RA 8187).'
            ],
            [
                'name' => 'Solo Parent Leave',
                'default_days' => 7,
                'is_paid' => true,
                'description' => 'Additional leave for solo parents (RA 8972).'
            ],
            [
                'name' => 'VAWC Leave',
                'default_days' => 10,
                'is_paid' => true,
                'description' => 'Leave for victims of Violence Against Women and their Children (RA 9262).'
            ],
            [
                'name' => 'Bereavement Leave',
                'default_days' => 3,
                'is_paid' => true,
                'description' => 'Compassionate leave for the death of an immediate family member.'
            ],
            [
                'name' => 'Emergency Leave',
                'default_days' => 3,
                'is_paid' => true,
                'description' => 'Leave for urgent personal or family matters.'
            ],
        ];

        foreach ($types as $type) {
            LeaveType::updateOrCreate(
                ['name' => $type['name']],
                [
                    'id' => Str::uuid(),
                    'default_days' => $type['default_days'],
                    'is_paid' => $type['is_paid'],
                    // If you added a description column to your migration
                    // 'description' => $type['description'], 
                ]
            );
        }
    }
}
