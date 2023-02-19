<?php

namespace App\Http;

use App\Http\Middleware\DebugBarMessageLogger;
use App\Http\Middleware\DungeonRouteContextLogger;
use App\Http\Middleware\EncryptCookies;
use App\Http\Middleware\LegalAgreed;
use App\Http\Middleware\OnlyAjax;
use App\Http\Middleware\ReadOnlyMode;
use App\Http\Middleware\RedirectIfAuthenticated;
use App\Http\Middleware\TrimStrings;
use App\Http\Middleware\VerifyCsrfToken;
use App\Http\Middleware\ViewCacheBuster;
use BeyondCode\ServerTiming\Middleware\ServerTimingMiddleware;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Auth\Middleware\AuthenticateWithBasicAuth;
use Illuminate\Auth\Middleware\Authorize;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Foundation\Http\Kernel as HttpKernel;
use Illuminate\Foundation\Http\Middleware\CheckForMaintenanceMode;
use Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull;
use Illuminate\Foundation\Http\Middleware\ValidatePostSize;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class Kernel extends HttpKernel
{
    /**
     * The application's global HTTP middleware stack.
     *
     * These middleware are run during every request to your application.
     *
     * @var array
     */
    protected $middleware = [
        ServerTimingMiddleware::class,
        CheckForMaintenanceMode::class,
        ValidatePostSize::class,
        TrimStrings::class,
        ConvertEmptyStringsToNull::class,
    ];

    /**
     * The application's route middleware groups.
     *
     * @var array
     */
    protected $middlewareGroups = [
        'web' => [
            EncryptCookies::class,
            AddQueuedCookiesToResponse::class,
            StartSession::class,
            // \Illuminate\Session\Middleware\AuthenticateSession::class,
            ShareErrorsFromSession::class,
            VerifyCsrfToken::class,
            SubstituteBindings::class,
        ],

        'api' => [
            'throttle:60,1',
            'bindings',
        ],
    ];

    /**
     * The application's route middleware.
     *
     * These middleware may be assigned to groups or used individually.
     *
     * @var array
     */
    protected $routeMiddleware = [
        'auth'                         => Authenticate::class,
        'auth.basic'                   => AuthenticateWithBasicAuth::class,
        'bindings'                     => SubstituteBindings::class,
        'can'                          => Authorize::class,
        'guest'                        => RedirectIfAuthenticated::class,
        'throttle'                     => ThrottleRequests::class,
        'ajax'                         => OnlyAjax::class,
        'viewcachebuster'              => ViewCacheBuster::class,
        'legal_agreed'                 => LegalAgreed::class,
        'debugbarmessagelogger'        => DebugBarMessageLogger::class,
        'dungeon_route_context_logger' => DungeonRouteContextLogger::class,
        'read_only_mode'               => ReadOnlyMode::class,
    ];
}
