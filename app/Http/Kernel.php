<?php

namespace App\Http;

use App\Http\Middleware\ApiAuthentication;
use App\Http\Middleware\DebugBarMessageLogger;
use App\Http\Middleware\DebugInfoContextLogger;
use App\Http\Middleware\EncryptCookies;
use App\Http\Middleware\LegalAgreed;
use App\Http\Middleware\OnlyAjax;
use App\Http\Middleware\ReadOnlyMode;
use App\Http\Middleware\RedirectIfAuthenticated;
use App\Http\Middleware\TracksUserIpAddress;
use App\Http\Middleware\TrimStrings;
use App\Http\Middleware\TrustProxies;
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
            TrustProxies::class,
        ],

        'api' => [
            ThrottleRequests::class . ':60,1',
            'bindings',
            'debug_info_context_logger' => DebugInfoContextLogger::class,
            'read_only_mode'            => ReadOnlyMode::class,
            'authentication'            => ApiAuthentication::class,
            TrustProxies::class,
        ],
    ];

    /**
     * The application's middleware aliases.
     *
     * Aliases may be used to conveniently assign middleware to routes and groups.
     *
     * @var array
     */
    protected $middlewareAliases = [
        'auth'                      => Authenticate::class,
        'auth.basic'                => AuthenticateWithBasicAuth::class,
        'bindings'                  => SubstituteBindings::class,
        'can'                       => Authorize::class,
        'guest'                     => RedirectIfAuthenticated::class,
        'throttle'                  => ThrottleRequests::class,
        'ajax'                      => OnlyAjax::class,
        'viewcachebuster'           => ViewCacheBuster::class,
        'legal_agreed'              => LegalAgreed::class,
        'debugbarmessagelogger'     => DebugBarMessageLogger::class,
        'debug_info_context_logger' => DebugInfoContextLogger::class,
        'read_only_mode'            => ReadOnlyMode::class,
        'track_ip'                  => TracksUserIpAddress::class,
    ];
}
