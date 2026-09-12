<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Permission;
use App\Models\Role;
use App\Models\ShiftTemplate;
use App\Models\SubscriptionEvent;
use App\Models\User;
use App\Services\Organizations\OrganizationProvisioningService;
use App\Tenancy\TenantContext;
use Database\Seeders\AdminSeeder;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class OrganizationSetupTest extends TestCase
{
    use RefreshDatabase;

    public function test_fresh_setup_creates_a_working_owner_and_retries_preserve_password_and_custom_defaults(): void
    {
        $this->artisan('platform:setup', ['--admin-email' => 'owner@example.test'])
            ->expectsQuestion('Administrator password (12+ characters, mixed case, number and symbol)', 'SetupPassword!123')
            ->expectsQuestion('Confirm administrator password', 'SetupPassword!123')
            ->assertSuccessful();

        $owner = User::where('email', 'owner@example.test')->firstOrFail();
        $this->assertTrue(Hash::check('SetupPassword!123', $owner->password));
        $this->assertTrue($owner->is_active);
        $this->assertSame('Admin', $owner->role->name);
        $this->assertGreaterThan(0, Permission::count());
        $this->assertGreaterThan(0, Role::where('name', 'User')->firstOrFail()->permissions()->count());
        $shift = ShiftTemplate::firstOrFail();
        $shift->update(['name' => 'Customized shift']);
        $originalHash = $owner->password;
        $permission = Permission::firstOrFail();
        $userRole = Role::where('name', 'User')->firstOrFail();
        $userRole->permissions()->sync([$permission->id]);

        $this->artisan('platform:setup', ['--no-interaction' => true])->assertSuccessful();
        $this->assertSame(1, User::count());
        $this->assertSame($originalHash, $owner->fresh()->password);
        $this->assertSame('Customized shift', $shift->fresh()->name);
        $this->assertSame([$permission->id], $userRole->permissions()->pluck('permissions.id')->all());
        $this->postJson(route('auth.login'), ['email' => $owner->email, 'password' => 'SetupPassword!123'])->assertOk();
    }

    public function test_mismatched_password_creates_no_owner_or_partial_defaults(): void
    {
        $this->artisan('platform:setup', ['--admin-email' => 'owner@example.test'])
            ->expectsQuestion('Administrator password (12+ characters, mixed case, number and symbol)', 'SetupPassword!123')
            ->expectsQuestion('Confirm administrator password', 'DifferentPassword!123')
            ->assertFailed();
        $this->assertSame(0, User::count());
        $this->assertSame(0, Role::count());
    }

    public function test_provisioning_rejects_false_invitation_without_a_password(): void
    {
        config()->set('platform.provisioning_key', 'test-key');
        foreach ([false, 0, '0'] as $flag) {
            $this->withHeader('X-Platform-Provisioning-Key', 'test-key')
                ->postJson(route('platform.organizations.store'), array_merge($this->attributes(), [
                    'send_owner_invitation' => $flag,
                    'admin_password' => null,
                ]))->assertUnprocessable()->assertJsonValidationErrors('admin_password');
        }
        $this->assertDatabaseMissing('organizations', ['slug' => 'setup-test']);
    }

    public function test_invitation_provisioning_normalizes_boolean_and_reports_log_mail_as_not_sent(): void
    {
        config()->set('platform.provisioning_key', 'test-key');
        config()->set('mail.default', 'log');
        $response = $this->withHeader('X-Platform-Provisioning-Key', 'test-key')
            ->postJson(route('platform.organizations.store'), array_merge($this->attributes(), [
                'send_owner_invitation' => '1',
                'admin_password' => null,
            ]));
        $response->assertCreated()->assertJsonPath('data.owner_invitation.mail_delivered', false)
            ->assertJsonPath('data.owner_invitation.mail_status', 'not_sent');
        $organization = Organization::where('slug', 'setup-test')->firstOrFail();
        app(TenantContext::class)->run($organization, function (): void {
            $this->assertSame(0, User::count());
            $this->assertGreaterThan(0, Role::where('name', 'User')->firstOrFail()->permissions()->count());
        });
    }

    public function test_new_workspace_login_url_and_authentication_are_tenant_specific(): void
    {
        config()->set('tenancy.base_domain', 'staging.example.test');
        config()->set('app.frontend_url', 'https://staging.example.test');
        $service = app(OrganizationProvisioningService::class);
        $a = $service->provision($this->attributes());
        $b = $service->provision(array_merge($this->attributes(), ['slug' => 'other', 'admin_password' => 'OtherPassword!123']));
        $this->assertSame('https://setup-test.staging.example.test/login', app(\App\Services\Organizations\OrganizationWorkspaceUrl::class)->login($a));
        $this->postJson('https://setup-test.staging.example.test/backend/api/v1/auth/login', ['email' => 'owner@example.test', 'password' => 'SetupPassword!123'])->assertOk();
        $this->postJson('https://other.staging.example.test/backend/api/v1/auth/login', ['email' => 'owner@example.test', 'password' => 'SetupPassword!123'])->assertUnprocessable();
        $this->postJson('https://other.staging.example.test/backend/api/v1/auth/login', ['email' => 'owner@example.test', 'password' => 'OtherPassword!123'])->assertOk();
        app(TenantContext::class)->run($b, function (): void {
            $this->assertSame(1, SubscriptionEvent::count());
        });
    }

    public function test_default_seeding_has_no_known_account_and_explicit_demo_seeding_is_blocked_in_production(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->assertSame(0, User::count());
        $this->app->instance('env', 'production');
        $this->expectException(\RuntimeException::class);
        app(AdminSeeder::class)->run();
    }

    public function test_cli_uses_shared_provisioning_and_existing_workspace_recovery_command(): void
    {
        $this->artisan('organizations:create', ['slug' => 'cli-test', 'name' => 'CLI Test', '--admin-email' => 'owner@example.test', '--plan' => 'basic_free'])
            ->expectsQuestion('Administrator password (12+ characters, mixed case, number and symbol)', 'SetupPassword!123')
            ->expectsQuestion('Confirm administrator password', 'SetupPassword!123')
            ->assertSuccessful();
        $organization = Organization::where('slug', 'cli-test')->firstOrFail();
        $this->assertSame('active', $organization->subscription_status);
        $this->assertNull($organization->trial_ends_at);
        $this->assertSame('PH', $organization->country_code);
        $this->assertSame('Asia/Manila', $organization->timezone);
        $this->artisan('organizations:create', ['slug' => 'cli-test', 'name' => 'CLI Test'])
            ->expectsOutput('Workspace already exists. Use platform:setup --organization=cli-test to initialize missing defaults or its first administrator.')
            ->assertFailed();
        $this->artisan('platform:setup', ['--organization' => 'cli-test', '--no-interaction' => true])->assertSuccessful();
        $this->assertSame(0, User::count()); // Test context is restored to legacy.
        app(TenantContext::class)->run($organization, fn () => $this->assertSame(1, User::count()));
    }

    private function attributes(): array
    {
        return ['slug' => 'setup-test', 'name' => 'Setup Test', 'country_code' => 'PH', 'timezone' => 'Asia/Manila', 'plan_code' => 'basic_free', 'admin_email' => 'owner@example.test', 'admin_password' => 'SetupPassword!123'];
    }
}
