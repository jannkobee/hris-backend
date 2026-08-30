<?php

namespace App\Services\Auth;

use App\Mail\PasswordResetRequested;
use App\Models\PasswordResetRequest;
use App\Models\User;
use App\Models\UserSetting;
use App\Repository\User\UserRepositoryInterface;
use App\Services\AuditLog\AuditLogServiceInterface;
use App\Services\Plans\PlanEntitlementService;
use App\Services\Utils\ResponseServiceInterface;
use App\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthService implements AuthServiceInterface
{
    private UserRepositoryInterface $userRepository;

    private ResponseServiceInterface $responseService;

    private AuditLogServiceInterface $auditLogService;

    public function __construct(
        UserRepositoryInterface $userRepository,
        ResponseServiceInterface $responseService,
        AuditLogServiceInterface $auditLogService,
        private readonly TenantContext $tenantContext,
        private readonly PlanEntitlementService $planEntitlements,
        private readonly MfaService $mfa
    ) {
        $this->userRepository = $userRepository;
        $this->responseService = $responseService;
        $this->auditLogService = $auditLogService;
    }

    public function headers(): array
    {
        return [
            'Authorization' => request()->header('Authorization'),
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Access-Control-Allow-Credentials' => true,
        ];
    }

    public function login(array $params, bool $isGoogleAuthenticated = false): array
    {
        $user = $this->userRepository->getUserByEmail($params['email']);

        if ($user
            && $this->tenantContext->belongsToCurrentOrganization($user->organization_id)
            && Hash::check(Arr::get($params, 'password'), $user->password)) {
            $user->loadMissing('organization');

            if ($this->mfaEnabled($user)) {
                return [
                    'mfa_required' => true,
                    'challenge' => $this->createMfaChallenge($user),
                ];
            }

            return $this->issueToken($user);
        }

        throw ValidationException::withMessages([
            'invalid_user_name_or_password' => 'Invalid E-mail or Password',
        ]);
    }

    public function logout(): JsonResponse
    {
        $user = Auth::user();

        if ($user instanceof User) {
            $user->currentAccessToken()?->delete();
        }

        return $this->responseService->resolveResponse('Logout Successful', null);
    }

    public function authUser(): JsonResponse
    {
        $authUser = Auth::user();

        if (! $authUser) {
            return $this->responseService->resolveResponse('Unauthenticated', null, 401);
        }

        $user = User::query()
            ->with('organization', 'role.permissions', 'settings', 'employee.department', 'employee.position')
            ->find($authUser->id);

        if (! $user || ! $this->tenantContext->belongsToCurrentOrganization($user->organization_id)) {
            return $this->responseService->resolveResponse('Unauthenticated', null, 401);
        }

        $this->attachOrganizationPlan($user);

        $settings = $user->settings
            ->mapWithKeys(function ($setting) {
                return [$setting->setting_key => $this->normalizeSettingValue($setting->setting_value)];
            })
            ->toArray();
        $user->setAttribute('settings', $settings);

        return $this->responseService->resolveResponse('Authenticated User', $user);
    }

    public function getSettings(): JsonResponse
    {
        $authUser = Auth::user();

        if (! $authUser) {
            return $this->responseService->resolveResponse('Unauthenticated', null, 401);
        }

        $settings = UserSetting::where('user_id', $authUser->id)
            ->get()
            ->mapWithKeys(function ($setting) {
                return [$setting->setting_key => $this->normalizeSettingValue($setting->setting_value)];
            })
            ->toArray();

        return $this->responseService->resolveResponse('User settings', $settings);
    }

    private function serializeSettingValue(mixed $value): string
    {
        if (is_array($value)) {
            return json_encode($value);
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        return (string) $value;
    }

    private function normalizeSettingValue(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        $decoded = json_decode($value, true);

        if (json_last_error() === JSON_ERROR_NONE) {
            return $decoded;
        }

        if ($value === 'true') {
            return true;
        }

        if ($value === 'false') {
            return false;
        }

        return $value;
    }

    public function updateSettings(array $params): array
    {
        $authUser = Auth::user();

        if (! $authUser) {
            return ['response' => $this->responseService->resolveResponse('Unauthenticated', null, 401)];
        }

        $settings = [];

        foreach ($params as $key => $value) {
            $setting = UserSetting::updateOrCreate(
                ['user_id' => $authUser->id, 'setting_key' => $key],
                ['setting_value' => $this->serializeSettingValue($value)]
            );

            $settings[$key] = $this->normalizeSettingValue($setting->setting_value);
        }

        return ['response' => $this->responseService->resolveResponse('Settings updated', $settings)];
    }

    public function verifyMfaChallenge(array $params): array
    {
        $challenge = Cache::get($this->mfaChallengeKey($params['challenge']));
        $user = is_array($challenge) ? User::query()->find($challenge['user_id'] ?? null) : null;

        if (! $user instanceof User || ! $this->mfaEnabled($user) || ! $this->mfa->verify($user, $params['code'])) {
            throw ValidationException::withMessages(['code' => 'The verification code is invalid or has expired.']);
        }

        Cache::forget($this->mfaChallengeKey($params['challenge']));

        return $this->issueToken($user);
    }

    public function requestPasswordReset(array $params): array
    {
        $user = $this->userRepository->getUserByEmail($params['email']);

        // Always return success so this endpoint cannot be used to enumerate accounts.
        if (! $user instanceof User) {
            return ['message' => 'If an account exists for that email, a reset link has been sent.'];
        }

        $token = Str::random(64);
        $expiresInMinutes = (int) config('auth_security.password_reset_minutes');

        PasswordResetRequest::query()->where('user_id', $user->id)->delete();
        PasswordResetRequest::create([
            'user_id' => $user->id,
            'token' => Hash::make($token),
            'expires_at' => now()->addMinutes($expiresInMinutes),
        ]);

        $resetUrl = config('auth_security.frontend_url').'/reset-password?'.http_build_query([
            'email' => $user->email,
            'token' => $token,
        ]);
        Mail::to($user->email)->queue(new PasswordResetRequested($user, $resetUrl, $expiresInMinutes));

        $this->auditLogService->insertLog($user, 'password reset requested', ['record_id' => $user->id]);

        return ['message' => 'If an account exists for that email, a reset link has been sent.'];
    }

    public function resetPassword(array $params): array
    {
        $user = $this->userRepository->getUserByEmail($params['email']);
        $reset = $user instanceof User
            ? PasswordResetRequest::query()->where('user_id', $user->id)->first()
            : null;

        if (! $user instanceof User || ! $reset || $reset->expires_at->isPast() || ! Hash::check($params['token'], $reset->token)) {
            throw ValidationException::withMessages(['token' => 'This password reset link is invalid or has expired.']);
        }

        $user->forceFill(['password' => $params['password']])->save();
        $user->tokens()->delete();
        $reset->delete();
        $this->auditLogService->insertLog($user, 'password reset completed', ['record_id' => $user->id]);

        return ['message' => 'Your password has been reset. Sign in with your new password.'];
    }

    public function updatePassword(array $params): array
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return ['response' => $this->responseService->resolveResponse('Unauthenticated', null, 401)];
        }

        $user->forceFill(['password' => $params['password']])->save();
        $currentTokenId = $user->currentAccessToken()?->id;
        $user->tokens()->when($currentTokenId, fn ($query) => $query->whereKeyNot($currentTokenId))->delete();
        $this->auditLogService->insertLog($user, 'password updated', ['record_id' => $user->id]);

        return ['response' => $this->responseService->resolveResponse('Password updated. Other device sessions were signed out.', null)];
    }

    public function mfaStatus(): array
    {
        $user = Auth::user();

        return [
            'enabled' => $user instanceof User && $this->mfaEnabled($user),
            'confirmed_at' => $user instanceof User ? $user->two_factor_confirmed_at?->toIso8601String() : null,
            'recovery_codes_remaining' => $user instanceof User ? count((array) $user->two_factor_recovery_codes) : 0,
        ];
    }

    public function startMfaSetup(): array
    {
        $user = Auth::user();
        if (! $user instanceof User) {
            throw ValidationException::withMessages(['user' => 'Unauthenticated.']);
        }

        $secret = $this->mfa->generateSecret();
        $user->forceFill([
            'two_factor_secret' => $secret,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();

        return ['secret' => $secret, 'otpauth_url' => $this->mfa->otpauthUrl($user, $secret)];
    }

    public function confirmMfa(array $params): array
    {
        $user = Auth::user();
        if (! $user instanceof User || ! $user->two_factor_secret || ! $this->mfa->verifyTotp($user->two_factor_secret, $params['code'])) {
            throw ValidationException::withMessages(['code' => 'The verification code is invalid.']);
        }

        $recoveryCodes = $this->mfa->recoveryCodes();
        $user->forceFill([
            'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => $this->mfa->hashedRecoveryCodes($recoveryCodes),
        ])->save();
        $this->auditLogService->insertLog($user, 'two-factor authentication enabled', ['record_id' => $user->id]);

        return ['recovery_codes' => $recoveryCodes];
    }

    public function disableMfa(array $params): array
    {
        $user = Auth::user();
        if (! $user instanceof User || ! $this->mfaEnabled($user) || ! $this->mfa->verify($user, $params['code'])) {
            throw ValidationException::withMessages(['code' => 'The verification code is invalid.']);
        }

        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();
        $this->auditLogService->insertLog($user, 'two-factor authentication disabled', ['record_id' => $user->id]);

        return ['message' => 'Two-factor authentication disabled.'];
    }

    public function sessions(): array
    {
        $user = Auth::user();
        if (! $user instanceof User) {
            return [];
        }

        $currentTokenId = $user->currentAccessToken()?->id;

        return $user->tokens()
            ->latest()
            ->get(['id', 'name', 'last_used_at', 'expires_at', 'created_at'])
            ->map(fn ($token) => [
                'id' => $token->id,
                'name' => $token->name,
                'last_used_at' => $token->last_used_at?->toIso8601String(),
                'expires_at' => $token->expires_at?->toIso8601String(),
                'created_at' => $token->created_at?->toIso8601String(),
                'is_current' => $token->id === $currentTokenId,
            ])->all();
    }

    public function revokeSession(int $tokenId): void
    {
        $user = Auth::user();
        if (! $user instanceof User) {
            return;
        }

        $user->tokens()->whereKey($tokenId)->delete();
    }

    public function revokeOtherSessions(): void
    {
        $user = Auth::user();
        if (! $user instanceof User) {
            return;
        }

        $currentTokenId = $user->currentAccessToken()?->id;
        $user->tokens()->when($currentTokenId, fn ($query) => $query->whereKeyNot($currentTokenId))->delete();
    }

    private function attachOrganizationPlan(User $user): void
    {
        if (! $user->organization) {
            return;
        }

        $user->organization->setAttribute(
            'plan',
            $this->planEntitlements->payload($user->organization)
        );
    }

    private function mfaEnabled(User $user): bool
    {
        return (bool) $user->two_factor_secret && $user->two_factor_confirmed_at !== null;
    }

    private function createMfaChallenge(User $user): string
    {
        $challenge = Str::random(64);
        Cache::put(
            $this->mfaChallengeKey($challenge),
            ['user_id' => $user->id],
            now()->addMinutes((int) config('auth_security.mfa_challenge_minutes'))
        );

        return $challenge;
    }

    private function mfaChallengeKey(string $challenge): string
    {
        return 'auth:mfa:'.$this->tenantContext->id().':'.$challenge;
    }

    private function issueToken(User $user): array
    {
        $this->auditLogService->loginLog('login', ['email' => $user->email]);
        $user->loadMissing('organization');
        $this->attachOrganizationPlan($user);
        $expiresAt = now()->addMinutes((int) config('auth_security.token_lifetime_minutes'));
        $device = substr((string) request()->userAgent(), 0, 100) ?: 'Browser session';

        return [
            'token' => $user->createToken($device, ['*'], $expiresAt)->plainTextToken,
            'expires_at' => $expiresAt->toIso8601String(),
            'user' => $user,
        ];
    }
}
