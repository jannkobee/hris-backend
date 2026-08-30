<?php

namespace App\Services\Auth;

interface AuthServiceInterface
{
    public function login(array $params);

    public function logout();

    public function authUser();

    public function getSettings();

    public function updateSettings(array $params);

    public function verifyMfaChallenge(array $params);

    public function requestPasswordReset(array $params);

    public function resetPassword(array $params);

    public function updatePassword(array $params);

    public function mfaStatus();

    public function startMfaSetup();

    public function confirmMfa(array $params);

    public function disableMfa(array $params);

    public function sessions();

    public function revokeSession(int $tokenId);

    public function revokeOtherSessions();
}
