<?php

namespace App\Http\Middleware;

use App\Service\ReadOnlyMode\ReadOnlyModeServiceInterface;
use Auth;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Teapot\StatusCode\RFC\RFC7231;

class ReadOnlyMode
{
    private const array ROUTE_WHITELIST = [
        'login',
        'logout',
        'ajax/heatmap/data',
    ];

    public function __construct(private readonly ReadOnlyModeServiceInterface $readOnlyModeService)
    {
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->method() !== 'GET' &&
            $this->readOnlyModeService->isReadOnlyForUser(Auth::user()) &&
            // Some routes are allowed to be accessed in read-only mode
            !in_array($request->path(), self::ROUTE_WHITELIST)
        ) {
            if ($request->ajax() || $request->isJson()) {
                return response(json_encode([
                    'message' => 'Service Unavailable - site is in read-only mode',
                ]), RFC7231::SERVICE_UNAVAILABLE);
            } else {
                return response('Service Unavailable - site is in read-only mode', RFC7231::SERVICE_UNAVAILABLE);
            }
        }

        return $next($request);
    }
}
