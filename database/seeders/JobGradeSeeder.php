<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\JobGrade;

class JobGradeSeeder extends Seeder
{
    public function run(): void
    {
        $grades = [
            [
                'code' => 'JG-01',
                'name' => 'Job Grade 1',
                'description' => 'Entry Level',
                'min_salary' => 15000,
                'max_salary' => 20000,
            ],
            [
                'code' => 'JG-02',
                'name' => 'Job Grade 2',
                'description' => 'Junior Level',
                'min_salary' => 20001,
                'max_salary' => 30000,
            ],
            [
                'code' => 'JG-03',
                'name' => 'Job Grade 3',
                'description' => 'Mid Level',
                'min_salary' => 30001,
                'max_salary' => 45000,
            ],
            [
                'code' => 'JG-04',
                'name' => 'Job Grade 4',
                'description' => 'Senior Level',
                'min_salary' => 45001,
                'max_salary' => 65000,
            ],
            [
                'code' => 'JG-05',
                'name' => 'Job Grade 5',
                'description' => 'Lead / Supervisor',
                'min_salary' => 65001,
                'max_salary' => 90000,
            ],
        ];

        foreach ($grades as $grade) {
            JobGrade::updateOrCreate(
                ['code' => $grade['code']],
                $grade
            );
        }
    }
}
