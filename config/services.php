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

    'mercadopago' => [
        'access_token'       => env('MP_ACCESS_TOKEN'),
        'is_sandbox'         => env('MP_SANDBOX', true),
        'notification_url'   => env('MP_NOTIFICATION_URL'),
        'back_url_success'   => env('MP_BACK_URL_SUCCESS', 'http://localhost:5173/client/payment/success'),
        'back_url_failure'   => env('MP_BACK_URL_FAILURE', 'http://localhost:5173/client/payment/failure'),
        'back_url_pending'   => env('MP_BACK_URL_PENDING', 'http://localhost:5173/client/payment/pending'),
    ],

];
