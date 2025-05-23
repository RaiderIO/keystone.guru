<?php

namespace App\Http\Middleware\Api;

use App\Service\User\UserServiceInterface;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Teapot\StatusCode;

class ApiAuthentication
{
    public function __construct(private readonly UserServiceInterface $userService)
    {
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!app()->runningUnitTests() && !$this->userService->loginAsUserFromAuthenticationHeader($request)) {
            return response()->json(['error' => __('exceptions.handler.unauthenticated')], StatusCode::UNAUTHORIZED);
        }

        return $next($request);
    }
}
