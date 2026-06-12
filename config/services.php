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

    /*
    | Firebase Cloud Messaging (FCM).
    |
    | The HTTP v1 transport credentials live in config/firebase.php (published
    | from kreait/laravel-firebase). Set FIREBASE_CREDENTIALS or
    | FIREBASE_CREDENTIALS_JSON in .env — see that config file for details.
    |
    | `legacy_server_key` is the pre-2024 FCM Legacy HTTP API server key,
    | preserved here only because the web's App\Models\Notification still
    | references it. The endpoint it targets was shut down by Google on
    | 20 June 2024, so this is effectively a dead path — but we keep the
    | value out of source so it isn't a committed credential.
    */
    'fcm' => [
        'legacy_server_key' => env('FCM_LEGACY_SERVER_KEY'),
    ],

];
