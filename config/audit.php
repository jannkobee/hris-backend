<?php

return [
    'retention_years' => (int) env('AUDIT_LOG_RETENTION_YEARS', 7),
    'signing_key' => env('AUDIT_LOG_SIGNING_KEY', env('APP_KEY')),
];
