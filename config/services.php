<?php

return [

    'mailgun' => [
        'domain'   => env('MAILGUN_DOMAIN'),
        'secret'   => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme'   => 'https',
    ],

    'sparkpost' => [
        'secret' => env('SPARKPOST_SECRET'),
    ],

    'stripe' => [
        'model'  => App\Models\User::class,
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
