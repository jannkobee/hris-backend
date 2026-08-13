<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
