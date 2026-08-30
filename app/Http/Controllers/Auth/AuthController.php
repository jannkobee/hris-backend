<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\DisableMfaRequest;
use App\Http\Requests\ForgotPasswordRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\MfaChallengeRequest;
use App\Http\Requests\MfaCodeRequest;
use App\Http\Requests\ResetPasswordRequest;
use App\Http\Requests\StartMfaSetupRequest;
use App\Http\Requests\UpdatePasswordRequest;
use App\Services\Auth\AuthServiceInterface;
use App\Services\Utils\ResponseServiceInterface;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    private AuthServiceInterface $authService;

    private ResponseServiceInterface $responseService;

    public function __construct(
        AuthServiceInterface $authService,
        ResponseServiceInterface $responseService,
    ) {
        $this->authService = $authService;
        $this->responseService = $responseService;
    }

    public function login(LoginRequest $request)
    {
        $data = $this->authService->login($request->validated());

        return $this->responseService->resolveResponse(
            ($data['mfa_required'] ?? false) ? 'Two-factor authentication required.' : 'Login successful.',
            $data
        );
    }

    public function logout()
    {
        return $this->authService->logout();
    }

    public function verifyMfaChallenge(MfaChallengeRequest $request)
    {
        return $this->responseService->resolveResponse(
            'Login successful.',
            $this->authService->verifyMfaChallenge($request->validated())
        );
    }

    public function forgotPassword(ForgotPasswordRequest $request)
    {
        $result = $this->authService->requestPasswordReset($request->validated());

        return $this->responseService->resolveResponse($result['message'], null);
    }

    public function resetPassword(ResetPasswordRequest $request)
    {
        $result = $this->authService->resetPassword($request->validated());

        return $this->responseService->resolveResponse($result['message'], null);
    }

    public function updatePassword(UpdatePasswordRequest $request)
    {
        return $this->authService->updatePassword($request->validated())['response'];
    }

    public function mfaStatus()
    {
        return $this->responseService->resolveResponse(
            'Two-factor authentication status retrieved.',
            $this->authService->mfaStatus()
        );
    }

    public function startMfaSetup(StartMfaSetupRequest $request)
    {
        return $this->responseService->resolveResponse(
            'Two-factor authentication setup started.',
            $this->authService->startMfaSetup()
        );
    }

    public function confirmMfa(MfaCodeRequest $request)
    {
        return $this->responseService->resolveResponse(
            'Two-factor authentication enabled.',
            $this->authService->confirmMfa($request->validated())
        );
    }

    public function disableMfa(DisableMfaRequest $request)
    {
        $result = $this->authService->disableMfa($request->validated());

        return $this->responseService->resolveResponse($result['message'], null);
    }

    public function sessions()
    {
        return $this->responseService->resolveResponse(
            'Device sessions retrieved.',
            $this->authService->sessions()
        );
    }

    public function revokeSession(int $token)
    {
        $this->authService->revokeSession($token);

        return $this->responseService->resolveResponse('Device session signed out.', null);
    }

    public function revokeOtherSessions()
    {
        $this->authService->revokeOtherSessions();

        return $this->responseService->resolveResponse('Other device sessions signed out.', null);
    }

    public function authUser()
    {
        return $this->authService->authUser();
    }

    public function getSettings()
    {
        return $this->authService->getSettings();
    }

    public function updateSettings(Request $request)
    {
        $data = $request->all();

        return $this->authService->updateSettings($data)['response'];
    }
}
