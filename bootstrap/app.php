<?php

use App\Http\Middleware\Api\ApiAuthentication;
use App\Http\Middleware\DebugBarMessageLogger;
use App\Http\Middleware\DebugInfoContextLogger;
use App\Http\Middleware\LegalAgreed;
use App\Http\Middleware\OnlyAjax;
use App\Http\Middleware\PoweredBySwoole;
use App\Http\Middleware\ReadOnlyMode;
use App\Http\Middleware\TracksUserIpAddress;
use App\Http\Middleware\TrustProxies;
use App\Http\Middleware\ViewCacheBuster;
use Barryvdh\LaravelIdeHelper\IdeHelperServiceProvider;
use BeyondCode\ServerTiming\Middleware\ServerTimingMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Foundation\Http\Middleware\CheckForMaintenanceMode;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Jenssegers\Agent\AgentServiceProvider;
use Laratrust\LaratrustServiceProvider;
use Laravel\Tinker\TinkerServiceProvider;
use Rollbar\Laravel\RollbarServiceProvider;
use Sentry\Laravel\Integration;
use SocialiteProviders\Manager\ServiceProvider;

return Application::configure(basePath: dirname(__DIR__))
    ->withProviders([
        TinkerServiceProvider::class,
        IdeHelperServiceProvider::class,
        LaratrustServiceProvider::class,
        AgentServiceProvider::class,
        ServiceProvider::class,
        RollbarServiceProvider::class,
    ])
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        channels: __DIR__ . '/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->redirectGuestsTo(fn() => route('login'));
        $middleware->redirectUsersTo('/home');

        $middleware->validateCsrfTokens(except: [
            '*',
        ]);

        $middleware->append([
            ServerTimingMiddleware::class,
            CheckForMaintenanceMode::class,
            PoweredBySwoole::class,
        ]);

        $middleware->api([
            'authentication'            => ApiAuthentication::class,
            'debug_info_context_logger' => DebugInfoContextLogger::class,
            'read_only_mode'            => ReadOnlyMode::class,
        ]);

        $middleware->replace(\Illuminate\Http\Middleware\TrustProxies::class, TrustProxies::class);

        $middleware->alias([
            'ajax'                      => OnlyAjax::class,
            'bindings'                  => SubstituteBindings::class,
            'debug_info_context_logger' => DebugInfoContextLogger::class,
            'debugbarmessagelogger'     => DebugBarMessageLogger::class,
            'legal_agreed'              => LegalAgreed::class,
            'read_only_mode'            => ReadOnlyMode::class,
            'track_ip'                  => TracksUserIpAddress::class,
            'viewcachebuster'           => ViewCacheBuster::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        Integration::handles($exceptions);
    })->create();
