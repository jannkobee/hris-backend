<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use App\Services\Organizations\SubscriptionLifecycleService;
use App\Services\Plans\PlanEntitlementService;
use App\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class FreeBasicTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_signup_is_free_and_does_not_expire(): void
    {
        $this->postJson(route('public-apis.trial-signups.store'), [
            'organization_name' => 'Free Company', 'country_code' => 'PH', 'timezone' => 'Asia/Manila',
            'first_name' => 'Admin', 'last_name' => 'Owner', 'email' => 'owner@free.test',
            'password' => 'StrongPassword!123', 'password_confirmation' => 'StrongPassword!123', 'terms_accepted' => true,
        ])->assertCreated()->assertJsonPath('data.organization.plan_code', 'basic_free')
            ->assertJsonPath('data.organization.trial_ends_at', null)
            ->assertJsonPath('data.organization.subscription_status', 'active');
        $organization = Organization::where('slug', 'free-company')->firstOrFail();
        $this->travel(2)->years();
        $this->assertNull(app(SubscriptionLifecycleService::class)->reconcile($organization));
        $this->assertTrue($organization->subscriptionAllowsAccess());
        $this->assertTrue(app(PlanEntitlementService::class)->allows($organization, 'attendance_corrections'));
        $this->assertFalse(app(PlanEntitlementService::class)->allows($organization, 'payroll'));
    }

    public function test_capacity_blocks_creation_and_reactivation_but_allows_edits_and_ended_employment(): void
    {
        $organization = app(TenantContext::class)->organization();
        $organization->update(['plan_code' => 'basic_free', 'employee_limit' => 100]);
        for ($i = 1; $i <= 10; $i++) {
            Employee::create(['employee_no' => 'FREE-'.$i]);
        }
        $employee = Employee::firstOrFail();
        $employee->update(['basic_monthly_salary' => 100]);
        try {
            Employee::create(['employee_no' => 'ELEVEN']);
            $this->fail('Eleventh active employee was accepted.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('organization', $exception->errors());
        }
        $employee->update(['employment_effective_to' => now()->subDay()->toDateString()]);
        Employee::create(['employee_no' => 'REPLACEMENT']);
        try {
            $employee->update(['employment_effective_to' => null]);
            $this->fail('Reactivation above capacity was accepted.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('organization', $exception->errors());
        }
        $this->assertNotNull($employee->fresh()->employment_effective_to);
        $this->assertSame(11, Employee::count());
    }

    public function test_other_tenants_do_not_consume_places_and_legacy_basic_keeps_its_limit(): void
    {
        $legacy = app(TenantContext::class)->organization();
        $legacy->update(['plan_code' => 'basic', 'employee_limit' => null]);
        $this->assertSame(50, app(PlanEntitlementService::class)->employeeLimit($legacy));
        Employee::create(['employee_no' => 'OTHER']);
        $free = Organization::create(['slug' => 'another-free', 'name' => 'Free', 'country_code' => 'PH',
            'timezone' => 'Asia/Manila', 'plan_code' => 'basic_free', 'status' => 'active', 'subscription_status' => 'active']);
        app(TenantContext::class)->run($free, function () {
            for ($i = 1; $i <= 10; $i++) {
                Employee::create(['employee_no' => 'FREE-'.$i]);
            }
            $this->assertSame(10, Employee::count());
        });
        $this->assertSame(1, Employee::count());
    }

    public function test_employee_api_keeps_permissions_and_returns_capacity_validation(): void
    {
        $organization = app(TenantContext::class)->organization();
        $organization->update(['plan_code' => 'basic_free', 'subscription_status' => 'active']);
        $role = Role::create(['name' => 'Admin']);
        $admin = User::factory()->create(['role_id' => $role->id]);
        for ($i = 1; $i <= 10; $i++) {
            Employee::create(['employee_no' => 'LIMIT-'.$i]);
        }
        $payload = ['user_id' => $admin->id, 'employee_no' => 'API-ELEVEN'];
        $this->postJson(route('employees.store'), $payload)->assertUnauthorized();
        $this->actingAs($admin, 'sanctum')->postJson(route('employees.store'), $payload)
            ->assertUnprocessable()->assertJsonValidationErrors('organization');
        $this->assertSame(10, Employee::count());
    }
}
