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

    /*
    |--------------------------------------------------------------------------
    | Google OAuth
    |--------------------------------------------------------------------------
    */

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI', 'http://localhost:11111/auth/google/callback'),
        'picker_api_key' => env('GOOGLE_PICKER_API_KEY'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Anthropic API
    |--------------------------------------------------------------------------
    */

    'anthropic' => [
        'api_key' => env('ANTHROPIC_API_KEY'),
        'model_chat' => env('ANTHROPIC_MODEL_CHAT', 'claude-opus-4-20250514'),
        'model_summarize' => env('ANTHROPIC_MODEL_SUMMARIZE', 'claude-3-5-haiku-20241022'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Stripe Billing
    |--------------------------------------------------------------------------
    */

    'stripe' => [
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
        'price_pro' => env('STRIPE_PRICE_PRO'),
        'price_team' => env('STRIPE_PRICE_TEAM'),
        'price_enterprise' => env('STRIPE_PRICE_ENTERPRISE'),
    ],

    /*
    |--------------------------------------------------------------------------
    | DeepL Translation
    |--------------------------------------------------------------------------
    */

    'deepl' => [
        'api_key' => env('DEEPL_API_KEY'),
    ],

];
