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

    // Arabic AI voice for adhkar and kalimas: gemini (default) or azure
    'arabic_voice' => [
        'provider' => env('ARABIC_VOICE_PROVIDER', 'gemini'),
    ],

    // Google AI Studio API key (aistudio.google.com > Get API key)
    'gemini' => [
        'key' => env('GEMINI_API_KEY'),
        'voice' => env('GEMINI_TTS_VOICE', 'Charon'),
        'tts_model' => env('GEMINI_TTS_MODEL', 'gemini-2.5-flash-preview-tts'),
    ],

    // Microsoft Azure Speech (Azure portal > Speech service > Keys and Endpoint)
    'azure_tts' => [
        'key' => env('AZURE_TTS_KEY'),
        'region' => env('AZURE_TTS_REGION'),           // e.g. uaenorth, westeurope
        'voice' => env('AZURE_TTS_VOICE', 'ar-SA-HamedNeural'),
    ],

];
