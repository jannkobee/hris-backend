<?php

return [
    // Payment providers should update subscriptions through the platform API
    // or a verified webhook handler. This defines the access grace window
    // after a paid billing period expires.
    'past_due_grace_days' => (int) env('BILLING_PAST_DUE_GRACE_DAYS', 7),
    'currency' => env('BILLING_CURRENCY', 'usd'),
    'regional_prices' => [
        'PH' => [
            'currency' => 'php',
            'locale' => 'en-PH',
            'prices' => [
                'starter' => ['month' => (int) env('STRIPE_PH_STARTER_MONTHLY_CENTAVOS', 199000), 'year' => (int) env('STRIPE_PH_STARTER_YEARLY_CENTAVOS', 1990000)],
                'growth' => ['month' => (int) env('STRIPE_PH_GROWTH_MONTHLY_CENTAVOS', 499000), 'year' => (int) env('STRIPE_PH_GROWTH_YEARLY_CENTAVOS', 4990000)],
                'business' => ['month' => (int) env('STRIPE_PH_BUSINESS_MONTHLY_CENTAVOS', 1199000), 'year' => (int) env('STRIPE_PH_BUSINESS_YEARLY_CENTAVOS', 11990000)],
            ],
        ],
    ],
    'stripe' => [
        'api_base' => env('STRIPE_API_BASE', 'https://api.stripe.com'),
        'secret_key' => env('STRIPE_SECRET_KEY', ''),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET', ''),
        // Replace these launch defaults with your approved commercial pricing.
        'prices' => [
            'starter' => ['month' => (int) env('STRIPE_STARTER_MONTHLY_CENTS', 3900), 'year' => (int) env('STRIPE_STARTER_YEARLY_CENTS', 39000)],
            'growth' => ['month' => (int) env('STRIPE_GROWTH_MONTHLY_CENTS', 9900), 'year' => (int) env('STRIPE_GROWTH_YEARLY_CENTS', 99000)],
            'business' => ['month' => (int) env('STRIPE_BUSINESS_MONTHLY_CENTS', 24900), 'year' => (int) env('STRIPE_BUSINESS_YEARLY_CENTS', 249000)],
        ],
    ],
];
