<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
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

    // À vérifier contre la doc Orange Developer Center Mali une fois les
    // credentials marchand obtenus — voir App\Domain\Payment\Gateways\OrangeMoneyGateway.
    'orange_money' => [
        'base_url' => env('ORANGE_MONEY_BASE_URL', 'https://api.orange.com'),
        'country' => env('ORANGE_MONEY_COUNTRY', 'ml'),
        'client_id' => env('ORANGE_MONEY_CLIENT_ID'),
        'client_secret' => env('ORANGE_MONEY_CLIENT_SECRET'),
        'merchant_key' => env('ORANGE_MONEY_MERCHANT_KEY'),
        'return_url' => env('ORANGE_MONEY_RETURN_URL'),
        'cancel_url' => env('ORANGE_MONEY_CANCEL_URL'),
        'notif_url' => env('ORANGE_MONEY_NOTIF_URL'),
    ],

    // À vérifier contre la doc Cloudflare Stream une fois un compte
    // disponible — voir App\Domain\Video\Gateways\CloudflareStreamGateway.
    'cloudflare_stream' => [
        'account_id' => env('CLOUDFLARE_STREAM_ACCOUNT_ID'),
        'api_token' => env('CLOUDFLARE_STREAM_API_TOKEN'),
        'max_duration_seconds' => (int) env('CLOUDFLARE_STREAM_MAX_DURATION_SECONDS', 7200),
    ],

];
