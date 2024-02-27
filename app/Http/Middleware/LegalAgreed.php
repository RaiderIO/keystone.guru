<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LegalAgreed
{
    /**
     * Handle an incoming request.
     *
     * @param  Request  $request
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (Auth::check() && ! Auth::user()->legal_agreed) {
            return response('You must agree to the terms for service to proceed.', 403);
        }

        return $next($request);
    }
}
