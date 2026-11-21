<?php

namespace App\Http\Middleware;

use App\Features\ApiTokens;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Pennant\Feature;
use Symfony\Component\HttpFoundation\Response;

/**
 * Runs on every Passport route, so a signed-in user can only grant an OAuth client access while the ApiTokens
 * feature is active for them. Sessionless requests (a client exchanging a code at /oauth/token) carry no user and
 * pass; a guest on /oauth/authorize passes too, and is refused here once Passport has sent them through the login.
 */
class EnsureOAuthAuthorizationIsAllowed
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->hasSession() && Auth::guard(config('passport.guard'))->check() && !Feature::active(ApiTokens::class)) {
            abort(404);
        }

        return $next($request);
    }
}
