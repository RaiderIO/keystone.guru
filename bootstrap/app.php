<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withProviders([
        \Laravel\Tinker\TinkerServiceProvider::class,
        \Barryvdh\LaravelIdeHelper\IdeHelperServiceProvider::class,
        \Laratrust\LaratrustServiceProvider::class,
        \Jenssegers\Agent\AgentServiceProvider::class,
        \SocialiteProviders\Manager\ServiceProvider::class,
        \Rollbar\Laravel\RollbarServiceProvider::class,
    ])
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->redirectUsersTo('/home');

        $middleware->validateCsrfTokens(except: [
            '*',
        ]);

        $middleware->append([
            \BeyondCode\ServerTiming\Middleware\ServerTimingMiddleware::class,
            \Illuminate\Foundation\Http\Middleware\CheckForMaintenanceMode::class,
            \App\Http\Middleware\PoweredBySwoole::class,
        ]);

        $middleware->api([
            \App\Http\'authentication' => ApiAuthentication::class,
            \App\Http\'debug_info_context_logger' => DebugInfoContextLogger::class,
            \App\Http\'read_only_mode'            => ReadOnlyMode::class,
        ]);

        $middleware->replace(\Illuminate\Http\Middleware\TrustProxies::class, \App\Http\Middleware\TrustProxies::class);

        $middleware->alias([
            'ajax' => \App\Http\Middleware\OnlyAjax::class,
            'bindings' => \Illuminate\Routing\Middleware\SubstituteBindings::class,
            'debug_info_context_logger' => \App\Http\Middleware\DebugInfoContextLogger::class,
            'debugbarmessagelogger' => \App\Http\Middleware\DebugBarMessageLogger::class,
            'legal_agreed' => \App\Http\Middleware\LegalAgreed::class,
            'read_only_mode' => \App\Http\Middleware\ReadOnlyMode::class,
            'track_ip' => \App\Http\Middleware\TracksUserIpAddress::class,
            'viewcachebuster' => \App\Http\Middleware\ViewCacheBuster::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
