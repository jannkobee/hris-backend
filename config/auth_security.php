<?php

return [
    'token_lifetime_minutes' => (int) env('AUTH_TOKEN_LIFETIME_MINUTES', 480),
    'mfa_challenge_minutes' => (int) env('AUTH_MFA_CHALLENGE_MINUTES', 5),
    'password_reset_minutes' => (int) env('AUTH_PASSWORD_RESET_MINUTES', 60),
    'mfa_issuer' => env('AUTH_MFA_ISSUER', env('APP_NAME', 'HRIS')),
    'frontend_url' => rtrim((string) env('FRONTEND_URL', 'http://localhost:3000'), '/'),
];
