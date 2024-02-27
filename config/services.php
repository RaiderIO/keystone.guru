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
        'domain'   => env('MAILGUN_DOMAIN'),
        'secret'   => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme'   => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key'    => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'sparkpost' => [
        'secret' => env('SPARKPOST_SECRET'),
    ],

    'stripe' => [
        'model'  => App\User::class,
        'key'    => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
    ],

    'google' => [
        'client_id'     => env('GOOGLE_SIGNIN_CLIENT_ID'),
        'client_secret' => env('GOOGLE_SIGNIN_CLIENT_SECRET'),
        'redirect'      => '/login/google/callback',
    ],

    'battlenet' => [
        'client_id'     => env('BATTLE_NET_SIGNIN_CLIENT_ID'),
        'client_secret' => env('BATTLE_NET_SIGNIN_CLIENT_SECRET'),
        'redirect'      => env('APP_URL') . '/login/battlenet/callback',
    ],

    'discord' => [
        'client_id'     => env('DISCORD_SIGNIN_CLIENT_ID'),
        'client_secret' => env('DISCORD_SIGNIN_CLIENT_SECRET'),
        'redirect'      => env('APP_URL') . '/login/discord/callback',
    ],

];
