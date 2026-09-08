<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayrollCountryTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_philippine_admin_cannot_access_payroll_routes(): void
    {
        $organization = app(TenantContext::class)->organization();
        $organization->update(['country_code' => 'US', 'plan_code' => 'enterprise', 'subscription_status' => 'active']);
        $role = Role::create(['name' => 'Admin']);
        $admin = User::factory()->create(['role_id' => $role->id]);
        foreach (['payroll.index', 'payroll-adjustments.index', 'statutory-reports.index'] as $route) {
            $this->actingAs($admin, 'sanctum')->getJson(route($route))->assertForbidden();
        }
        $organization->update(['country_code' => 'PH']);
        $admin->unsetRelation('organization');
        $this->actingAs($admin, 'sanctum')->getJson(route('payroll.index'))->assertOk();
    }
}
