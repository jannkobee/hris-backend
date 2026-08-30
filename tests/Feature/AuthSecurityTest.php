<?php

namespace Tests\Feature;

use App\Mail\PasswordResetRequested;
use App\Models\PasswordResetRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AuthSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_password_reset_requests_are_tenant_scoped_and_do_not_expose_account_existence(): void
    {
        Mail::fake();
        $user = User::factory()->create(['email' => 'employee@example.test']);

        $this->postJson(route('auth.password.forgot'), ['email' => $user->email])
            ->assertOk()
            ->assertJsonPath('message', 'If an account exists for that email, a reset link has been sent.');

        $this->assertDatabaseHas('password_reset_requests', [
            'organization_id' => $user->organization_id,
            'user_id' => $user->id,
        ]);
        Mail::assertQueued(PasswordResetRequested::class, fn (PasswordResetRequested $mail) => $mail->user->is($user));

        $this->postJson(route('auth.password.forgot'), ['email' => 'missing@example.test'])
            ->assertOk()
            ->assertJsonPath('message', 'If an account exists for that email, a reset link has been sent.');
    }

    public function test_password_reset_changes_credentials_and_revokes_all_device_sessions(): void
    {
        $user = User::factory()->create(['email' => 'employee@example.test']);
        $user->createToken('Device one');
        $token = str_repeat('a', 64);
        PasswordResetRequest::create([
            'user_id' => $user->id,
            'token' => Hash::make($token),
            'expires_at' => now()->addMinutes(30),
        ]);

        $this->postJson(route('auth.password.reset'), [
            'email' => $user->email,
            'token' => $token,
            'password' => 'NewSecurePassword!123',
            'password_confirmation' => 'NewSecurePassword!123',
        ])->assertOk();

        $this->assertTrue(Hash::check('NewSecurePassword!123', $user->fresh()->password));
        $this->assertSame(0, $user->fresh()->tokens()->count());
        $this->assertDatabaseMissing('password_reset_requests', ['user_id' => $user->id]);
    }

    public function test_mfa_login_requires_and_consumes_a_recovery_code_before_issuing_a_token(): void
    {
        $recoveryCode = 'AABBCCDDEE';
        $user = User::factory()->create([
            'email' => 'mfa@example.test',
            'two_factor_secret' => 'JBSWY3DPEHPK3PXP',
            'two_factor_recovery_codes' => [Hash::make($recoveryCode)],
            'two_factor_confirmed_at' => now(),
        ]);

        $login = $this->postJson(route('auth.login'), [
            'email' => $user->email,
            'password' => 'secret',
        ]);

        $login->assertOk()
            ->assertJsonPath('data.mfa_required', true)
            ->assertJsonMissingPath('data.token');

        $this->postJson(route('auth.mfa.challenge'), [
            'challenge' => $login->json('data.challenge'),
            'code' => $recoveryCode,
        ])->assertOk()
            ->assertJsonStructure(['message', 'data' => ['token', 'expires_at', 'user']]);

        $this->assertSame([], $user->fresh()->two_factor_recovery_codes);
    }

    public function test_current_device_can_revoke_another_device_session_without_revoking_itself(): void
    {
        $user = User::factory()->create();
        $current = $user->createToken('Current device');
        $other = $user->createToken('Other device');

        $this->withToken($current->plainTextToken)
            ->deleteJson(route('auth.sessions.destroy', $other->accessToken->id))
            ->assertOk();

        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $other->accessToken->id]);
        $this->assertDatabaseHas('personal_access_tokens', ['id' => $current->accessToken->id]);
    }
}
