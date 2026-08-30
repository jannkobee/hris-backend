<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Tenancy\TenantContext;
use Illuminate\Database\Seeder;

class OrganizationDefaultsSeeder extends Seeder
{
    public function run(): void
    {
        Organization::query()->where('status', Organization::STATUS_ACTIVE)->get()
            ->each(fn (Organization $organization) => $this->seed($organization));
    }

    public function seed(Organization $organization): void
    {
        app(TenantContext::class)->run($organization, function (): void {
            $this->call([
                EmploymentStatusSeeder::class,
                DepartmentSeeder::class,
                PositionSeeder::class,
                JobGradeSeeder::class,
                LeaveTypeSeeder::class,
                ScheduledTaskSeeder::class,
            ]);
        });
    }
}
