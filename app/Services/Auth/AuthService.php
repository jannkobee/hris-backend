<?php

namespace App\Services\Auth;

use App\Models\User;
use App\Models\UserSetting;
use App\Repository\User\UserRepositoryInterface;
use App\Services\AuditLog\AuditLogServiceInterface;
use App\Services\Utils\ResponseServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthService implements AuthServiceInterface
{
    private UserRepositoryInterface $userRepository;

    private ResponseServiceInterface $responseService;

    private AuditLogServiceInterface $auditLogService;

    public function __construct(UserRepositoryInterface $userRepository, ResponseServiceInterface $responseService, AuditLogServiceInterface $auditLogService)
    {
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

        // if (!$user->is_admin) {
        //     throw ValidationException::withMessages(['login_error' => 'User must be an admin to login.']);
        // }

        if (Hash::check(Arr::get($params, 'password'), $user->password)) {
            $this->auditLogService->loginLog('login', ['email' => $params['email']]);

            return [
                'token' => $user->createToken('UserLogin')->plainTextToken,
                'user' => $user,
            ];
        }

        throw ValidationException::withMessages([
            'invalid_user_name_or_password' => 'Invalid E-mail or Password',
        ]);
    }

    public function logout(): JsonResponse
    {
        $user = Auth::user();

        if ($user instanceof User) {
            $user->tokens()->where('tokenable_id', $user->id)->latest()->first()?->delete();
        }

        return $this->responseService->resolveResponse('Logout Successful', null);
    }

    public function authUser(): JsonResponse
    {
        $authUser = Auth::user();

        if (! $authUser) {
            return $this->responseService->resolveResponse('Unauthenticated', null, 401);
        }

        $user = User::withoutGlobalScopes()->find($authUser->id)->load('role.permissions', 'settings', 'employee.department', 'employee.position');
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
}
