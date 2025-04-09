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

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    // Red Provider Portal Konfiguration
    // Für Testzwecke kann man 'enabled' auf false setzen,
    // dann wird der MockOrderService verwendet
    'red_provider_portal' => [
        'enabled' => env('RED_PROVIDER_PORTAL_ENABLED', false),
        'url' => env('RED_PROVIDER_PORTAL_URL', 'http://localhost:3000'),
        'client_id' => env('RED_PROVIDER_PORTAL_CLIENT_ID', 'Fun'),
        'client_secret' => env('RED_PROVIDER_PORTAL_CLIENT_SECRET', '=work@red'),
    ],

];
