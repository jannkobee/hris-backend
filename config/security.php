<?php

return [
    'content_security_policy' => env(
        'CONTENT_SECURITY_POLICY',
        "default-src 'self'; base-uri 'self'; form-action 'self'; frame-ancestors 'none'; object-src 'none'; script-src 'self'; style-src 'self' 'unsafe-inline'; img-src 'self' data: blob: https:; font-src 'self' data:; connect-src 'self' https: wss:; media-src 'self' blob:; worker-src 'self' blob:; manifest-src 'self'"
    ),

    'hsts' => [
        'enabled' => (bool) env('SECURITY_HSTS_ENABLED', false),
        'value' => env('SECURITY_HSTS_VALUE', 'max-age=31536000; includeSubDomains'),
    ],

    'encrypted_fields' => [
        \App\Models\User::class => ['two_factor_secret', 'two_factor_recovery_codes'],
        \App\Models\EmployeeDocument::class => ['document_number', 'notes'],
        \App\Models\SsoConfiguration::class => ['client_secret'],
        \App\Models\WebhookSubscription::class => ['signing_secret'],
    ],
];
