<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * https://stackoverflow.com/questions/32584700/how-to-prevent-laravel-routes-from-being-accessed-directly-i-e-non-ajax-reques
 */
class OnlyAjax
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->ajax()) {
            return response('Forbidden', 403);
        }

        return $next($request);
    }
}
