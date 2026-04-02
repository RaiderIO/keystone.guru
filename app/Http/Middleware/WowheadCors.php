<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class WowheadCors
{
    public function handle(Request $request, Closure $next): Response
    {
        $allowedOrigins = [
            'https://www.wowhead.com',
            'https://wowhead.com',
        ];

        $origin = $request->headers->get('Origin');

        if ($request->isMethod('OPTIONS')) {
            $response = response('', 204);
        } else {
            /** @var Response $response */
            $response = $next($request);
        }

        if ($origin !== null && in_array($origin, $allowedOrigins, true)) {
            $response->headers->set('Access-Control-Allow-Origin', $origin);
            $response->headers->set('Vary', 'Origin');
            $response->headers->set('Access-Control-Allow-Methods', 'POST, OPTIONS');
            $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, X-Import-Token');
            $response->headers->set('Access-Control-Max-Age', '86400');
        }

        return $response;
    }
}
