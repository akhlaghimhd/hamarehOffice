<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
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

    /*
    | OpenAI-compatible chat API (optional — same code for local & host).
    |
    | Local (Ollama):
    |   OPENAI_API_KEY=ollama
    |   OPENAI_BASE_URL=http://127.0.0.1:11434/v1
    |   OPENAI_MODEL=llama3.2
    |   OPENAI_JSON_MODE=false
    |
    | Host (OpenAI):
    |   OPENAI_API_KEY=sk-...
    |   OPENAI_BASE_URL=https://api.openai.com/v1
    |   OPENAI_MODEL=gpt-4o-mini
    |   OPENAI_JSON_MODE=true
    |
    | Empty OPENAI_API_KEY => local heuristic only (always safe offline).
    */
    'openai' => [
        'key' => env('OPENAI_API_KEY'),
        'base_url' => rtrim((string) env('OPENAI_BASE_URL', 'https://api.openai.com/v1'), '/'),
        'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
        'timeout' => (int) env('OPENAI_TIMEOUT', 45),
        // null = auto (false for Ollama-like URLs, true otherwise)
        'json_mode' => env('OPENAI_JSON_MODE'),
    ],

];
