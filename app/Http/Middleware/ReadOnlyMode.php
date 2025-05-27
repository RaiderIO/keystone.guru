<?php

namespace App\Http\Middleware;

use App\Service\ReadOnlyMode\ReadOnlyModeServiceInterface;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Teapot\StatusCode\RFC\RFC7231;

class ReadOnlyMode
{
    public function __construct(private readonly ReadOnlyModeServiceInterface $readOnlyModeService)
    {
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->method() !== 'GET' && $this->readOnlyModeService->isReadOnly()) {
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
