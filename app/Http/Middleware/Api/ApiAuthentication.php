<?php

namespace App\Http\Middleware\Api;

use App\Http\Middleware\Api\Logging\ApiAuthenticationLoggingInterface;
use App\Service\User\Dtos\BasicAuthenticationResult;
use App\Service\User\UserServiceInterface;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Teapot\StatusCode;

class ApiAuthentication
{
    public function __construct(
        private readonly UserServiceInterface              $userService,
        private readonly ApiAuthenticationLoggingInterface $log,
    ) {
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!app()->runningUnitTests()) {
            $result = $this->userService->loginAsUserFromAuthenticationHeader($request);

            if ($result !== BasicAuthenticationResult::Success) {
                // Every failure answers the same opaque 401 - the log line is the only place that says
                // whether the credentials were rejected or never made it here in the first place
                $this->log->handleAuthenticationFailed($result->value);

                return response()->json(['error' => __('exceptions.handler.unauthenticated')], StatusCode::UNAUTHORIZED);
            }
        }

        return $next($request);
    }
}
