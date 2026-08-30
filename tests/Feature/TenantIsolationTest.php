<?php

namespace Tests\Feature;

use App\Models\AppSetting;
use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use App\Services\AppSettings\AppSettingService;
use App\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use LogicException;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    private TenantContext $tenantContext;

    private Organization $legacyOrganization;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenantContext = app(TenantContext::class);
        $this->legacyOrganization = $this->tenantContext->organization();
    }

    public function test_tenant_models_are_isolated_and_natural_keys_are_tenant_relative(): void
    {
        $alphaRole = Role::create(['name' => 'Shared role']);
        $alphaUser = User::factory()->create(['email' => 'shared@example.test']);
        AppSetting::create([
            'key' => 'organization.company_name',
            'value' => json_encode('Alpha'),
        ]);

        $beta = $this->createOrganization('beta');
        $this->tenantContext->set($beta);

        $betaRole = Role::create(['name' => 'Shared role']);
        $betaUser = User::factory()->create(['email' => 'shared@example.test']);
        AppSetting::create([
            'key' => 'organization.company_name',
            'value' => json_encode('Beta'),
        ]);

        $this->assertSame(1, Role::query()->count());
        $this->assertSame(1, User::query()->count());
        $this->assertSame(1, AppSetting::query()->count());
        $this->assertNull(Role::query()->find($alphaRole->id));
        $this->assertNull(User::query()->find($alphaUser->id));
        $this->assertNotNull(Role::query()->find($betaRole->id));
        $this->assertNotNull(User::query()->find($betaUser->id));

        $this->assertSame(2, DB::table('roles')->where('name', 'Shared role')->count());
        $this->assertSame(2, DB::table('users')->where('email', 'shared@example.test')->count());
        $this->assertSame(2, DB::table('app_settings')->where('key', 'organization.company_name')->count());
    }

    public function test_tenant_models_fail_closed_without_a_context(): void
    {
        Role::create(['name' => 'Visible only with a tenant']);

        $this->tenantContext->clear();

        $this->assertSame(0, Role::query()->count());
        $this->assertSame(0, User::query()->count());
        $this->assertSame(0, AppSetting::query()->count());

        $this->expectException(LogicException::class);
        Role::create(['name' => 'Must fail']);
    }

    public function test_company_settings_and_cache_entries_are_isolated_by_organization(): void
    {
        Cache::flush();
        $settings = app(AppSettingService::class);

        $settings->update(['organization.company_name' => 'Alpha']);

        $beta = $this->createOrganization('beta');
        $this->tenantContext->set($beta);
        $settings->update(['organization.company_name' => 'Beta']);

        $this->assertSame('Beta', $settings->get('organization.company_name'));

        $this->tenantContext->set($this->legacyOrganization);
        $this->assertSame('Alpha', $settings->get('organization.company_name'));
    }

    public function test_login_is_scoped_by_host_and_unknown_or_suspended_hosts_are_rejected(): void
    {
        $this->legacyOrganization->update([
            'slug' => 'alpha',
            'plan_code' => Organization::PLAN_BASIC,
        ]);
        config()->set('tenancy.base_domain', 'hris.test');
        config()->set('tenancy.default_slug', 'alpha');

        $alphaRole = Role::create(['name' => 'User']);
        User::factory()->create([
            'role_id' => $alphaRole->id,
            'email' => 'person@example.test',
            'password' => 'alpha-password',
        ]);

        $beta = $this->createOrganization('beta');
        $this->tenantContext->set($beta);
        $betaRole = Role::create(['name' => 'User']);
        User::factory()->create([
            'role_id' => $betaRole->id,
            'email' => 'person@example.test',
            'password' => 'beta-password',
        ]);

        $this->postJson($this->tenantUrl('alpha', 'auth.login'), [
            'email' => 'person@example.test',
            'password' => 'alpha-password',
        ])
            ->assertOk()
            ->assertJsonPath('user.organization.slug', 'alpha')
            ->assertJsonPath('user.organization.plan.code', 'basic');

        $this->postJson($this->tenantUrl('beta', 'auth.login'), [
            'email' => 'person@example.test',
            'password' => 'alpha-password',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('invalid_user_name_or_password');

        $this->postJson($this->tenantUrl('unknown', 'auth.login'), [
            'email' => 'person@example.test',
            'password' => 'alpha-password',
        ])
            ->assertNotFound();

        $beta->update(['status' => Organization::STATUS_SUSPENDED]);

        $this->postJson($this->tenantUrl('beta', 'auth.login'), [
            'email' => 'person@example.test',
            'password' => 'beta-password',
        ])
            ->assertForbidden();
    }

    public function test_bearer_token_is_rejected_on_another_organization_host(): void
    {
        $this->legacyOrganization->update([
            'slug' => 'alpha',
            'plan_code' => Organization::PLAN_BASIC,
        ]);
        config()->set('tenancy.base_domain', 'hris.test');
        config()->set('tenancy.default_slug', 'alpha');

        $role = Role::create(['name' => 'User']);
        $user = User::factory()->create(['role_id' => $role->id]);
        $token = $user->createToken('tenant-test')->plainTextToken;
        $this->createOrganization('beta');

        $this->withToken($token)
            ->getJson($this->tenantUrl('alpha', 'auth.auth-user'))
            ->assertOk()
            ->assertJsonPath('data.organization.slug', 'alpha')
            ->assertJsonPath('data.organization.plan.code', 'basic');

        $this->withToken($token)
            ->getJson($this->tenantUrl('beta', 'auth.auth-user'))
            ->assertUnauthorized();
    }

    public function test_role_assignment_validation_rejects_a_role_from_another_organization(): void
    {
        $adminRole = Role::create(['name' => 'Admin']);
        $admin = User::factory()->create(['role_id' => $adminRole->id]);

        $beta = $this->createOrganization('beta');
        $this->tenantContext->set($beta);
        $foreignRole = Role::create(['name' => 'Foreign role']);
        $this->tenantContext->set($this->legacyOrganization);

        $this->actingAs($admin, 'sanctum')->postJson(route('users.store'), [
            'role_id' => $foreignRole->id,
            'first_name' => 'Tenant',
            'last_name' => 'Boundary',
            'email' => 'boundary@example.test',
            'gender' => 'Male',
            'birthday' => '1990-01-01',
        ])->assertUnprocessable()->assertJsonValidationErrors('role_id');
    }

    public function test_health_check_does_not_require_a_tenant_database_lookup(): void
    {
        config()->set('tenancy.base_domain', 'hris.test');

        $this->getJson('http://not-a-tenant.invalid'.route('health', [], false))
            ->assertOk()
            ->assertJsonPath('status', 'ok');
    }

    private function createOrganization(string $slug): Organization
    {
        return Organization::create([
            'slug' => $slug,
            'name' => ucfirst($slug),
            'timezone' => 'Asia/Manila',
            'plan_code' => Organization::PLAN_ENTERPRISE,
            'status' => Organization::STATUS_ACTIVE,
        ]);
    }

    private function tenantUrl(string $slug, string $routeName): string
    {
        return "http://{$slug}.hris.test".route($routeName, [], false);
    }
}
