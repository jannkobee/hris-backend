<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Position;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PositionSeeder extends Seeder
{
    public function run(): void
    {
        $positions = [
            'Human Resources' => [
                'HR Officer',
                'HR Assistant',
                'Recruitment Specialist',
            ],
            'Finance' => [
                'Accountant',
                'Finance Officer',
            ],
            'Information Technology' => [
                'Software Developer',
                'System Administrator',
                'IT Support',
            ],
            'Operations' => [
                'Operations Manager',
                'Operations Staff',
            ],
            'Sales' => [
                'Sales Executive',
                'Account Manager',
            ],
            'Marketing' => [
                'Marketing Officer',
                'Content Specialist',
            ],
            'Administration' => [
                'Administrative Assistant',
                'Office Clerk',
            ],
        ];

        foreach ($positions as $departmentName => $titles) {
            $department = Department::where('name', $departmentName)->first();

            if (!$department) {
                continue;
            }

            foreach ($titles as $title) {
                Position::firstOrCreate(
                    [
                        'department_id' => $department->id,
                        'name' => $title,
                    ],
                    [
                        'id' => Str::uuid(),
                        'description' => "{$title} position",
                    ]
                );
            }
        }
    }
}
