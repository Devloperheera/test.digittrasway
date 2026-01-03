<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    /*
    |--------------------------------------------------------------------------
    | SurePass Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for SurePass API integration used for document verification,
    | KYC services, Aadhaar verification, RC verification, etc.
    |
    */
    'surepass' => [
        'token' => env('SUREPASS_TOKEN'),
        'base_url' => env('SUREPASS_BASE_URL', 'https://kyc-api.surepass.io/api/v1'),
        'timeout' => env('SUREPASS_TIMEOUT', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | Razorpay Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Razorpay payment gateway integration including
    | subscription plans for monthly, quarterly, half-yearly, and yearly billing.
    |
    */
    'razorpay' => [
        // ✅ API Credentials
        'key' => env('RAZORPAY_KEY', 'rzp_live_RWQQ7ODAvmu7Td'),
        'secret' => env('RAZORPAY_SECRET', '9HiAoUMBmB00KgwduBUlLtko'),
        'webhook_secret' => env('RAZORPAY_WEBHOOK_SECRET', 'bfMG46YBsA8Z_M_'),

        // ✅ Subscription Plan IDs - CORRECTED WITH UNDERSCORE
        'plan_monthly' => env('RAZORPAY_PLAN_MONTHLY', 'plan_RZGQcSEA5lsfuB'),
        'plan_quarterly' => env('RAZORPAY_PLAN_QUARTERLY', 'plan_RZGSk1DK3xLykB'),
        'plan_half_yearly' => env('RAZORPAY_PLAN_HALF_YEARLY', 'plan_RZGTWAazo1yHvW'), // ✅ FIXED: underscore added
        'plan_yearly' => env('RAZORPAY_PLAN_YEARLY', 'plan_RZGUOTBe2pMzda'),

        // ✅ Plan Pricing (for reference)
        'pricing' => [
            'monthly' => [
                'price' => 249.00,
                'setup_fee' => 1.00,
                'duration' => '1 month',
                'interval' => 1,
                'interval_type' => 'month'
            ],
            'quarterly' => [
                'price' => 699.00,
                'setup_fee' => 1.00,
                'duration' => '3 months',
                'interval' => 3,
                'interval_type' => 'month'
            ],
            'half_yearly' => [
                'price' => 1199.00,
                'setup_fee' => 1.00,
                'duration' => '6 months',
                'interval' => 6,
                'interval_type' => 'month'
            ],
            'yearly' => [
                'price' => 1999.00,
                'setup_fee' => 1.00,
                'duration' => '12 months',
                'interval' => 12,
                'interval_type' => 'month'
            ],
        ],

        // ✅ Additional Settings
        'currency' => env('RAZORPAY_CURRENCY', 'INR'),
        'mode' => env('RAZORPAY_MODE', 'live'), // live or test

        // ✅ Webhook URL (for documentation)
        'webhook_url' => env('APP_URL', 'https://digittransway.com') . '/api/razorpay/webhook',
    ],

];
