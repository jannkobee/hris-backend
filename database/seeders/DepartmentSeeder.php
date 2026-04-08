<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $departments = [
            'Human Resources',
            'Finance',
            'Information Technology',
            'Operations',
            'Sales',
            'Marketing',
            'Administration',
        ];

        foreach ($departments as $name) {
            Department::firstOrCreate(
                ['name' => $name],
                [
                    'id' => Str::uuid(),
                    'description' => "{$name} Department",
                ]
            );
        }
    }
}
