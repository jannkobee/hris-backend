<?php

return [
    // This credential is for the platform control plane only. Keep it in a
    // secret manager in production; it must never be exposed to tenant users.
    'provisioning_key' => env('PLATFORM_PROVISIONING_KEY', ''),
    'trial_days' => (int) env('PUBLIC_TRIAL_DAYS', 14),
    'owner_invitation_days' => (int) env('OWNER_INVITATION_DAYS', 7),
    'owner_invitation_url' => env('OWNER_INVITATION_URL', rtrim((string) env('FRONTEND_URL', 'http://localhost:5173'), '/').'/accept-invite'),
    'health' => [
        'thresholds' => [
            'failed_jobs_warning' => (int) env('PLATFORM_FAILED_JOBS_WARNING', 1),
            'failed_jobs_critical' => (int) env('PLATFORM_FAILED_JOBS_CRITICAL', 5),
            'snapshot_interval_minutes' => (int) env('PLATFORM_HEALTH_SNAPSHOT_INTERVAL_MINUTES', 5),
        ],
    ],
];
