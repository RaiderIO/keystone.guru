<?php

use App\Models\CombatLog\ChallengeModeRun;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\User;
use App\Providers\ControllerServiceProvider;
use App\Providers\HelperServiceProvider;
use App\Providers\KeystoneGuruServiceProvider;
use App\Providers\LoggingServiceProvider;
use App\Providers\OctaneServiceProvider;
use App\Providers\RepositoryServiceProvider;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\ServiceProvider;

return [

    'type' => env('APP_TYPE', 'local'),

    'log' => env('APP_LOG', 'single'),

    'log_level' => env('LOG_LEVEL', 'debug'),

    'providers' => ServiceProvider::defaultProviders()->merge([
        /*
         * Package Service Providers...
         */
        Laravel\Tinker\TinkerServiceProvider::class,

        /*
         * Application Service Providers...
         */
        App\Providers\AppServiceProvider::class,
        App\Providers\AuthServiceProvider::class,
        App\Providers\BroadcastServiceProvider::class,
        App\Providers\EventServiceProvider::class,
        App\Providers\HorizonServiceProvider::class,
        App\Providers\RouteServiceProvider::class,
        App\Providers\TelescopeServiceProvider::class,

        /**
         * Custom
         */
        Barryvdh\LaravelIdeHelper\IdeHelperServiceProvider::class,
        Laratrust\LaratrustServiceProvider::class,
        Jenssegers\Agent\AgentServiceProvider::class,
        SocialiteProviders\Manager\ServiceProvider::class,
        Rollbar\Laravel\RollbarServiceProvider::class,

        /**
         * Keystone.guru Service Providers...
         */
        HelperServiceProvider::class,
        LoggingServiceProvider::class,
        RepositoryServiceProvider::class,
        OctaneServiceProvider::class,
        KeystoneGuruServiceProvider::class,
        ControllerServiceProvider::class,
    ])->toArray(),

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

];
