<?php

return [

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

    'unw_program_studi' => [
        'url' => env('UNW_PROGRAM_STUDI_API_URL', 'https://panel-web.unw.ac.id/api/unw-program-studi'),
        'verify_ssl' => env('UNW_PROGRAM_STUDI_VERIFY_SSL', true),
    ],

    'unwapi_siakad' => [
        'base' => env('UNW_API_SIAKAD_BASE_URL'),
        'email' => env('UNW_API_SIAKAD_EMAIL'),
        'password' => env('UNW_API_SIAKAD_PASSWORD'),
        'token_cache_key' => env('UNW_API_SIAKAD_TOKEN_CACHE_KEY', 'unwapi_siakad_token'),
        'token_cache_hours' => (int) env('UNW_API_SIAKAD_TOKEN_CACHE_HOURS', 12),
        'verify_ssl' => filter_var(env('UNW_API_SIAKAD_VERIFY_SSL', true), FILTER_VALIDATE_BOOLEAN),
    ],

];

