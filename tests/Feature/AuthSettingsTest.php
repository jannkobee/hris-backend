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
            ->patchJson(route('auth.settings.update'), ['theme' => 'dark']);

        $response->assertOk()
            ->assertJsonPath('data.theme', 'dark');

        $this->assertSame('dark', \App\Models\UserSetting::query()->where('user_id', $user->id)->where('setting_key', 'theme')->firstOrFail()->setting_value);
    }
}
