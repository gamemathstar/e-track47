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
    | Firebase Cloud Messaging (HTTP v1 API).
    |
    | `credentials` points to a Google service-account JSON file with the
    | "Firebase Cloud Messaging API" scope enabled. Mount it outside the repo
    | and reference it via FIREBASE_CREDENTIALS in .env. When this env var is
    | unset / file is missing, the FcmTransport falls back to a Null impl
    | that logs the would-have-sent payload and skips the network call —
    | useful for local dev and CI.
    */
    'fcm' => [
        'credentials' => env('FIREBASE_CREDENTIALS'),
    ],

];
