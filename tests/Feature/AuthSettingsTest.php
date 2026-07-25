<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_update_and_retrieve_settings(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->patchJson('/api/auth/settings', ['theme' => 'dark']);

        $response->assertOk()
            ->assertJsonPath('data.theme', 'dark');

        $this->assertDatabaseHas('user_settings', [
            'user_id' => $user->id,
            'setting_key' => 'theme',
            'setting_value' => 'dark',
        ]);
    }
}
