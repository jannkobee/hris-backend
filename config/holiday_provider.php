<?php

return [
    'base_url' => env('HOLIDAY_PROVIDER_BASE_URL', 'https://date.nager.at/api/v3'),
    'country_code' => env('HOLIDAY_PROVIDER_COUNTRY_CODE', 'PH'),
    'timeout_seconds' => (int) env('HOLIDAY_PROVIDER_TIMEOUT_SECONDS', 15),
];
