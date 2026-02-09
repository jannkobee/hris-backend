<?php

namespace Database\Seeders;

use App\Models\EmploymentStatus;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class EmploymentStatusSeeder extends Seeder
{
    public function run(): void
    {
        $statuses = [
            [
                'name' => 'Regular',
                'description' => 'Full-time regular employee',
            ],
            [
                'name' => 'Probationary',
                'description' => 'Employee under probationary period',
            ],
            [
                'name' => 'Contractual',
                'description' => 'Employee with a fixed-term contract',
            ],
            [
                'name' => 'Project-Based',
                'description' => 'Employee assigned to a specific project',
            ],
            [
                'name' => 'Part-Time',
                'description' => 'Employee working part-time hours',
            ],
            [
                'name' => 'Consultant',
                'description' => 'Independent consultant or external resource',
            ],
            [
                'name' => 'Intern',
                'description' => 'Intern or trainee',
            ],
            [
                'name' => 'Separated',
                'description' => 'No longer employed by the company',
            ],
        ];

        foreach ($statuses as $status) {
            EmploymentStatus::updateOrCreate(
                ['name' => $status['name']],
                [
                    'id' => Str::uuid(),
                    'description' => $status['description'],
                ]
            );
        }
    }
}
