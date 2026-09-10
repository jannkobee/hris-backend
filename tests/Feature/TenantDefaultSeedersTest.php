<?php

namespace Tests\Feature;

use App\Models\ApprovalWorkflow;
use App\Models\Department;
use App\Models\EmploymentStatus;
use App\Models\JobGrade;
use App\Models\LeaveType;
use App\Models\OvertimePolicy;
use App\Models\ShiftTemplate;
use App\Services\Organizations\OrganizationProvisioningService;
use App\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantDefaultSeedersTest extends TestCase
{
    use RefreshDatabase;

    public function test_newly_provisioned_organization_receives_all_default_seeders(): void
    {
        $provisioner = app(OrganizationProvisioningService::class);

        $org = $provisioner->provision([
            'name' => 'Acme Corporation',
            'slug' => 'acme-corp',
            'timezone' => 'Asia/Manila',
            'country_code' => 'PH',
            'plan_code' => 'basic_free',
            'admin_first_name' => 'John',
            'admin_last_name' => 'Doe',
            'admin_email' => 'admin@acme.test',
            'admin_password' => 'Password123!',
        ]);

        app(TenantContext::class)->run($org, function (): void {
            // 1. Shift Templates
            $this->assertSame(5, ShiftTemplate::count());
            $dayShift = ShiftTemplate::where('code', 'DAY-8-5')->first();
            $this->assertNotNull($dayShift);
            $this->assertSame('08:00', $dayShift->start_time);
            $this->assertSame('17:00', $dayShift->end_time);
            $this->assertSame([1, 2, 3, 4, 5], $dayShift->days_of_week);
            $this->assertTrue($dayShift->is_active);

            $nightShift = ShiftTemplate::where('code', 'NIGHT-10-7')->first();
            $this->assertNotNull($nightShift);
            $this->assertSame('22:00', $nightShift->start_time);

            // 2. Overtime Policies
            $this->assertSame(6, OvertimePolicy::count());
            $regDayOt = OvertimePolicy::where('day_type', 'regular_day')->first();
            $this->assertNotNull($regDayOt);
            $this->assertEquals('1.25', (string) $regDayOt->multiplier);

            $regHolidayOt = OvertimePolicy::where('day_type', 'regular_holiday')->first();
            $this->assertNotNull($regHolidayOt);
            $this->assertEquals('2.00', (string) $regHolidayOt->multiplier);

            // 3. Approval Workflows
            $this->assertSame(3, ApprovalWorkflow::count());
            foreach (['leave', 'overtime', 'attendance_correction'] as $resource) {
                $wf = ApprovalWorkflow::where('resource_type', $resource)->with('steps')->first();
                $this->assertNotNull($wf, "Workflow for {$resource} should exist");
                $this->assertTrue($wf->is_active);
                $this->assertCount(1, $wf->steps);
                $this->assertSame('manager', $wf->steps->first()->approver_type);
            }

            // 4. Existing defaults verified
            $this->assertGreaterThan(0, EmploymentStatus::count());
            $this->assertGreaterThan(0, Department::count());
            $this->assertGreaterThan(0, JobGrade::count());
            $this->assertGreaterThan(0, LeaveType::count());
        });
    }

    public function test_tenant_isolation_is_maintained_across_seeded_defaults(): void
    {
        $provisioner = app(OrganizationProvisioningService::class);

        $orgA = $provisioner->provision([
            'name' => 'Company A',
            'slug' => 'company-a',
            'timezone' => 'Asia/Manila',
            'country_code' => 'PH',
            'plan_code' => 'basic_free',
            'admin_first_name' => 'Alice',
            'admin_email' => 'alice@companya.test',
            'admin_password' => 'Password123!',
        ]);

        $orgB = $provisioner->provision([
            'name' => 'Company B',
            'slug' => 'company-b',
            'timezone' => 'Asia/Manila',
            'country_code' => 'PH',
            'plan_code' => 'basic_free',
            'admin_first_name' => 'Bob',
            'admin_email' => 'bob@companyb.test',
            'admin_password' => 'Password123!',
        ]);

        // When running in Org A, shift templates belong to Org A
        app(TenantContext::class)->run($orgA, function () use ($orgA): void {
            $shifts = ShiftTemplate::all();
            $this->assertCount(5, $shifts);
            foreach ($shifts as $shift) {
                $this->assertSame($orgA->id, $shift->organization_id);
            }
        });

        // When running in Org B, shift templates belong to Org B
        app(TenantContext::class)->run($orgB, function () use ($orgB): void {
            $shifts = ShiftTemplate::all();
            $this->assertCount(5, $shifts);
            foreach ($shifts as $shift) {
                $this->assertSame($orgB->id, $shift->organization_id);
            }
        });
    }
}
