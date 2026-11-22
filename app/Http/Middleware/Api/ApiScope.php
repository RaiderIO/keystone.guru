<?php

namespace App\Http\Middleware\Api;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use Teapot\StatusCode;

/**
 * Requires the OAuth access token of the request to carry at least one of the given scopes. A request
 * authenticated without a token (deprecated Basic authentication) predates scopes and keeps its full access.
 */
class ApiScope
{
    public function handle(Request $request, Closure $next, string ...$scopes): Response
    {
        /** @var User|null $user */
        $user  = Auth::user();
        $token = $user?->token();

        if ($token === null) {
            return $next($request);
        }

        foreach ($scopes as $scope) {
            if ($token->can($scope)) {
                return $next($request);
            }
        }

        return response()->json([
            'error' => __('exceptions.handler.missing_scope', ['scopes' => implode(', ', $scopes)]),
        ], StatusCode::FORBIDDEN);
    }
}
