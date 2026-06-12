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

    'mnemosyne' => [
        'endpoint' => env('MNEMOSYNE_ENDPOINT', 'https://memory.borgonovo.org'),
        'api_key'  => env('MNEMOSYNE_API_KEY'),
        'project'  => env('MNEMOSYNE_PROJECT', 'Ganaghello'),
        'scope'    => env('MNEMOSYNE_SCOPE', 'Private'),
        // Interruttore generale della sincronizzazione in scrittura (observer/job).
        // Disattivato durante import e seed massivi per non inondare la coda.
        'sync'     => env('MNEMOSYNE_SYNC', true),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
