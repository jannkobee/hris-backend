<?php

return [
    // This credential is for the platform control plane only. Keep it in a
    // secret manager in production; it must never be exposed to tenant users.
    'provisioning_key' => env('PLATFORM_PROVISIONING_KEY', ''),
];
