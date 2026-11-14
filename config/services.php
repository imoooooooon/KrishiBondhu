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

    /*
    | Gemini API (Google): AI guidance for F15 (soil), later F16 and F17. Used only by App\Services\GeminiClient.
    | The key stays on the server: it is read from .env and is never sent to the browser or written to logs.
    | "api_revision" is the Api-Revision header from Google's Interactions API examples.
    */
    'gemini' => [
        'api_key' => env('GEMINI_API_KEY'),
        'model' => env('GEMINI_MODEL', 'gemini-3.5-flash-lite'),
        'base_url' => env('GEMINI_BASE_URL', 'https://generativelanguage.googleapis.com/v1beta'),
        'api_revision' => env('GEMINI_API_REVISION', '2026-05-20'),
        'timeout' => (int) env('GEMINI_TIMEOUT', 20),             // seconds for the whole request
        'connect_timeout' => (int) env('GEMINI_CONNECT_TIMEOUT', 5),
        'max_output_tokens' => (int) env('GEMINI_MAX_OUTPUT_TOKENS', 4096),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
