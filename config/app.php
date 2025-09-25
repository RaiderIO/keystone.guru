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
