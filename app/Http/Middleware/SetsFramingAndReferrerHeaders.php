<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Pages may only be framed by the site itself, except the embed routes, which exist to be framed by other sites.
 */
class SetsFramingAndReferrerHeaders
{
    private const string EMBED_ROUTE_NAME_PATTERN = '/(^|\.)embed(old)?(\.|$)/';

    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        if (!$this->isEmbedRoute($request)) {
            $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
            $response->headers->set('Content-Security-Policy', "frame-ancestors 'self'", false);
        }

        return $response;
    }

    private function isEmbedRoute(Request $request): bool
    {
        $routeName = $request->route()?->getName();

        return $routeName !== null && preg_match(self::EMBED_ROUTE_NAME_PATTERN, $routeName) === 1;
    }
}
