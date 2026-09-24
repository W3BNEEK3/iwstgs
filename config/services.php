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

    'anthropic' => [
        'key' => env('ANTHROPIC_API_KEY'),
        // Implementation Plan §8.3 names claude-sonnet-4-20250514, written before newer
        // models existed. Defaulting to the current generation instead; override via env
        // if a specific pinned version is ever needed.
        'model' => env('ANTHROPIC_MODEL', 'claude-sonnet-5'),
    ],

    'gemini' => [
        'key' => env('GEMINI_API_KEY'),
        // gemini-2.5-flash was deprecated for new users during development — Google's
        // own API error pointed at this replacement. Override via env if it moves again.
        'model' => env('GEMINI_MODEL', 'gemini-3.6-flash'),
    ],

    // Which AI provider EvaluationService actually calls — 'claude' or 'gemini'.
    // A deliberate, explicit choice (not an automatic Claude-fails-so-try-Gemini
    // fallback): switch it in .env when Claude usage runs out, so it's always
    // clear after the fact which provider graded a given submission.
    'ai_evaluation' => [
        'provider' => env('AI_EVALUATION_PROVIDER', 'claude'),
    ],

];
