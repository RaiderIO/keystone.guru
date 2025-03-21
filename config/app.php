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

    /*
    |--------------------------------------------------------------------------
    | Application Name
    |--------------------------------------------------------------------------
    |
    | This value is the name of your application. This value is used when the
    | framework needs to place the application's name in a notification or
    | any other location as required by the application or its packages.
    */

    'name' => 'Keystone.guru',

    /*
    |--------------------------------------------------------------------------
    | Application Environment
    |--------------------------------------------------------------------------
    |
    | This value determines the "environment" your application is currently
    | running in. This may determine how you prefer to configure various
    | services your application utilizes. Set this in your ".env" file.
    |
    */

    'env'  => env('APP_ENV', 'production'),

    /*
    |--------------------------------------------------------------------------
    | Application Type
    |--------------------------------------------------------------------------
    |
    | This value determines the "type" of application Keystone.guru this is.
    | Can be a local environment, a staging environment, mapping environment or
    | production. A staging+mapping environment will have an APP_ENV of production
    | but have some different usages so we can use this switch to differentiate
    | between then.
    |
    */
    'type' => env('APP_TYPE', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Application Debug Mode
    |--------------------------------------------------------------------------
    |
    | When your application is in debug mode, detailed error messages with
    | stack traces will be shown on every error that occurs within your
    | application. If disabled, a simple generic error page is shown.
    |
    */

    'debug' => env('APP_DEBUG', false),

    /*
    |--------------------------------------------------------------------------
    | Application URL
    |--------------------------------------------------------------------------
    |
    | This URL is used by the console to properly generate URLs when using
    | the Artisan command line tool. You should set this to the root of
    | your application so that it is used when running Artisan tasks.
    |
    */

    'url' => env('APP_URL', 'http://localhost'),

    /*
    |--------------------------------------------------------------------------
    | Application Timezone
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default timezone for your application, which
    | will be used by the PHP date and date-time functions. We have gone
    | ahead and set this to a sensible default for you out of the box.
    |
    */

    'timezone' => 'UTC',

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

    'locale' => 'en_US',

    /*
    |--------------------------------------------------------------------------
    | Application Fallback Locale
    |--------------------------------------------------------------------------
    |
    | The fallback locale determines the locale to use when the current one
    | is not available. You may change the value to correspond to any of
    | the language folders that are provided through your application.
    |
    */

    'fallback_locale' => 'en_US',

    /*
    |--------------------------------------------------------------------------
    | Encryption Key
    |--------------------------------------------------------------------------
    |
    | This key is used by the Illuminate encrypter service and should be set
    | to a random, 32 character string, otherwise these encrypted strings
    | will not be safe. Please do this before deploying an application!
    |
    */

    'key' => env('APP_KEY'),

    'cipher' => 'AES-256-CBC',

    /*
    |--------------------------------------------------------------------------
    | Logging Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure the log settings for your application. Out of
    | the box, Laravel uses the Monolog PHP logging library. This gives
    | you a variety of powerful log handlers / formatters to utilize.
    |
    | Available Settings: "single", "daily", "syslog", "errorlog"
    |
    */

    'log' => env('APP_LOG', 'single'),

    'log_level' => env('LOG_LEVEL', 'debug'),

    /*
    |--------------------------------------------------------------------------
    | Autoloaded Service Providers
    |--------------------------------------------------------------------------
    |
    | The service providers listed here will be automatically loaded on the
    | request to your application. Feel free to add your own services to
    | this array to grant expanded functionality to your applications.
    |
    */

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
        Collective\Html\HtmlServiceProvider::class,
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

    /*
    |--------------------------------------------------------------------------
    | Class Aliases
    |--------------------------------------------------------------------------
    |
    | This array of class aliases will be registered when this application
    | is started. However, feel free to register as many as you wish as
    | the aliases are "lazy" loaded so they don't hinder performance.
    |
    */

    'aliases' => Facade::defaultAliases()->merge([
        'Agent'            => Jenssegers\Agent\Facades\Agent::class,
        'Form'             => Collective\Html\FormFacade::class,
        'GitHub'           => GrahamCampbell\GitHub\GitHubServiceProvider::class,
        'Html'             => Collective\Html\HtmlFacade::class,
        'Laratrust'        => Laratrust\LaratrustFacade::class,
        'Redis'            => Illuminate\Support\Facades\Redis::class,
        // Tinker models
        'DungeonRoute'     => DungeonRoute::class,
        'ChallengeModeRun' => ChallengeModeRun::class,
        'User'             => User::class,
    ])->toArray(),

];
