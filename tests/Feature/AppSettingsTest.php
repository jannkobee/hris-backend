<?php

namespace Tests\Feature;

use App\Models\AppSetting;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\AppSettings\AppSettingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AppSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_update_company_wide_settings(): void
    {
        $role = Role::create(['name' => 'Admin']);
        $admin = User::factory()->create(['role_id' => $role->id]);

        $response = $this->actingAs($admin, 'sanctum')->putJson(
            route('app-settings.update'),
            ['values' => [
                'attendance.location_capture_enabled' => false,
                'attendance.location_required' => false,
                'notifications.success_alerts_enabled' => false,
            ]]
        );

        $response->assertAccepted();
        $values = $response->json('data.values');

        $this->assertFalse($values['attendance.location_capture_enabled']);
        $this->assertFalse($values['notifications.success_alerts_enabled']);
    }

    public function test_non_admin_cannot_update_company_wide_settings(): void
    {
        $role = Role::create(['name' => 'User']);
        $user = User::factory()->create(['role_id' => $role->id]);

        $this->actingAs($user, 'sanctum')->putJson(
            route('app-settings.update'),
            ['values' => ['attendance.location_capture_enabled' => false]]
        )->assertForbidden();
    }

    public function test_settings_permissions_are_enforced_per_section(): void
    {
        $role = Role::create(['name' => 'Attendance Policy Owner']);
        $role->permissions()->attach(Permission::create([
            'model' => 'Audit and Settings',
            'name' => 'Manage attendance settings',
            'slug' => 'manage-attendance-settings',
        ]));
        $user = User::factory()->create(['role_id' => $role->id]);

        $response = $this->actingAs($user, 'sanctum')->putJson(
            route('app-settings.update'),
            ['values' => ['attendance.notes_enabled' => false]]
        )->assertAccepted();

        $this->assertFalse($response->json('data.values')['attendance.notes_enabled']);

        $this->actingAs($user, 'sanctum')->putJson(
            route('app-settings.update'),
            ['values' => ['organization.company_name' => 'Not allowed']]
        )->assertForbidden();
    }

    public function test_company_settings_are_loaded_once_and_updates_invalidate_the_cache(): void
    {
        Cache::flush();
        AppSetting::create([
            'key' => 'organization.company_name',
            'value' => json_encode('Acme'),
        ]);

        DB::flushQueryLog();
        DB::enableQueryLog();

        $settings = app(AppSettingService::class);
        $this->assertSame('Acme', $settings->get('organization.company_name'));
        $this->assertSame('Acme', $settings->get('organization.company_name'));

        $settingReads = collect(DB::getQueryLog())
            ->filter(fn (array $query): bool => str_contains($query['query'], 'from "app_settings"'))
            ->count();
        $this->assertSame(1, $settingReads);

        $settings->update(['organization.company_name' => 'Globex']);
        $this->assertSame('Globex', $settings->get('organization.company_name'));
    }

    public function test_timezone_options_come_from_the_php_timezone_database(): void
    {
        $role = Role::create(['name' => 'User']);
        $user = User::factory()->create(['role_id' => $role->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson(route('app-settings.index'))
            ->assertOk();

        $options = $response->json('data.definitions')['organization.timezone']['options'];

        $this->assertContains('UTC', $options);
        $this->assertContains('Asia/Manila', $options);
        $this->assertSame(\DateTimeZone::listIdentifiers(), $options);
    }
}
