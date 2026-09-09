<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => ['backend/api/v1/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => array_values(array_filter(array_unique(array_merge(
        ['http://localhost:5173', 'http://localhost:3000'],
        array_filter(explode(',', (string) env('CORS_ALLOWED_ORIGINS', ''))),
        array_filter([(string) env('FRONTEND_URL', '')])
    )))),

    'allowed_origins_patterns' => array_values(array_filter([
        env('TENANT_BASE_DOMAIN') ? '#^https?://.*\\.' . preg_quote((string) env('TENANT_BASE_DOMAIN'), '#') . '(:\\d+)?$#' : null,
    ])),

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,
];
