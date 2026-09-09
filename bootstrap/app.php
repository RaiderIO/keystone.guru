<?php

use App\Http\Middleware\AddsTraceIdToContext;
use App\Http\Middleware\Api\ApiAuthentication;
use App\Http\Middleware\Api\ApiAuthenticationThrottle;
use App\Http\Middleware\Api\ApiRole;
use App\Http\Middleware\BlockBannedIpAddresses;
use App\Http\Middleware\DebugBarMessageLogger;
use App\Http\Middleware\DebugInfoContextLogger;
use App\Http\Middleware\EnsureFeatureIsActive;
use App\Http\Middleware\LegalAgreed;
use App\Http\Middleware\OnlyAjax;
use App\Http\Middleware\PoweredBySwoole;
use App\Http\Middleware\ReadOnlyMode;
use App\Http\Middleware\ResetsMapFacadeStyleOverride;
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
use Illuminate\Routing\Middleware\ThrottleRequests;
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

        // Exempt from CSRF verification: the external webhook endpoints (called by GitHub / Wowhead
        // without a session) and the unauthenticated metric counters, which are also posted from
        // cross-site embed <iframe>s that carry no session cookie under same_site=lax. Every other
        // route sends a token.
        $middleware->validateCsrfTokens(except: [
            'webhook/*',
            'ajax/metric',
            'ajax/metric/*',
        ]);

        // Prepend so every log line of the request - including those of other global middleware - carries the trace_id
        $middleware->prepend(AddsTraceIdToContext::class);

        // Runs right after (the replaced) TrustProxies, so $request->ip() is already the real
        // visitor IP resolved from the forwarded chain - see BlockBannedIpAddresses for details.
        $middleware->append([
            BlockBannedIpAddresses::class,
            ServerTimingMiddleware::class,
            CheckForMaintenanceMode::class,
            PoweredBySwoole::class,
            // Must run before any controller that calls User::forceMapFacadeStyle() - it writes a
            // static that would otherwise leak into the next request on the same Octane worker
            ResetsMapFacadeStyleOverride::class,
        ]);

        $middleware->api([
            'authentication_throttle'   => ApiAuthenticationThrottle::class,
            'authentication'            => ApiAuthentication::class,
            'throttle_api_general'      => 'throttle:api-general',
            'debug_info_context_logger' => DebugInfoContextLogger::class,
            'read_only_mode'            => ReadOnlyMode::class,
        ]);

        // The order written above is not the order that runs: SortedMiddleware re-sorts the stack by the priority
        // list, and a middleware that is on that list (ThrottleRequests) moves ahead of one that is not. The
        // api-general limiter buckets by user id and exempts internal roles, so it has to see the user that
        // ApiAuthentication resolves - which only holds if the authentication middleware is on the list too, and
        // the throttle that bounds the authentication has to run ahead of it for the same reason.
        $middleware->prependToPriorityList(before: ThrottleRequests::class, prepend: ApiAuthentication::class);
        $middleware->prependToPriorityList(before: ApiAuthentication::class, prepend: ApiAuthenticationThrottle::class);

        $middleware->replace(\Illuminate\Http\Middleware\TrustProxies::class, TrustProxies::class);

        $middleware->alias([
            'ajax'                      => OnlyAjax::class,
            'api_role'                  => ApiRole::class,
            'bindings'                  => SubstituteBindings::class,
            'debug_info_context_logger' => DebugInfoContextLogger::class,
            'debugbarmessagelogger'     => DebugBarMessageLogger::class,
            'legal_agreed'              => LegalAgreed::class,
            'read_only_mode'            => ReadOnlyMode::class,
            'track_ip'                  => TracksUserIpAddress::class,
            'viewcachebuster'           => ViewCacheBuster::class,
            'feature_active'            => EnsureFeatureIsActive::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        Integration::handles($exceptions);
    })->create();
