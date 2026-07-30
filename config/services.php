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
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
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

    'boometo' => [
        'url' => env('BOOMETO_URL'),
        'token' => env('BOOMETO_TOKEN'),
        'amount_multiplier' => env('BOOMETO_AMOUNT_MULTIPLIER', 10),
        'invoice_ttl' => env('BOOMETO_INVOICE_TTL', 20),
        'frontend_callback_url' => env(
            'BOOMETO_FRONTEND_CALLBACK_URL',
            rtrim((string) env('FRONTEND_URL', 'http://localhost:3000'), '/').'/payment/callback'
        ),
    ],

];
