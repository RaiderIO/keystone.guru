<?php

use App\Models\CombatLog\ChallengeModeRun;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\User;
use Illuminate\Support\Facades\Facade;

return [

    'type' => env('APP_TYPE', 'local'),

    'log' => env('APP_LOG', 'single'),

    'log_level' => env('LOG_LEVEL', 'debug'),

    'aliases' => Facade::defaultAliases()->merge([
        'Agent'     => Jenssegers\Agent\Facades\Agent::class,
        'GitHub'    => GrahamCampbell\GitHub\GitHubServiceProvider::class,
        'Laratrust' => Laratrust\LaratrustFacade::class,
        'Redis'     => Illuminate\Support\Facades\Redis::class,
        // Tinker models
        'DungeonRoute'     => DungeonRoute::class,
        'ChallengeModeRun' => ChallengeModeRun::class,
        'User'             => User::class,
    ])->toArray(),

    /*
    |--------------------------------------------------------------------------
    | Application Locale Configuration
    |--------------------------------------------------------------------------
    |
    | The application locale determines the default locale that will be used
    | by the translation service provider. You are free to set this value
    | to any of the locales which will be supported by the application.
    |
    */
    'locale' => env('APP_LOCALE', 'en'),

    /*
    |--------------------------------------------------------------------------
    | Application Fallback Locale
    |--------------------------------------------------------------------------
    |
    | The fallback locale determines the locale to use when the current one
    | is not available. Change the value to correspond to any of the language
    | folders that are provided through your application.
    |
    */
    'fallback_locale' => env('APP_FALLBACK_LOCALE', 'en'),
];
