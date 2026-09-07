<?php

namespace App\Http\Middleware\Api;

use App\Http\Middleware\Api\Logging\ApiAuthenticationLoggingInterface;
use App\Service\User\Dtos\BasicAuthenticationResult;
use App\Service\User\UserServiceInterface;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;
use Teapot\StatusCode;

class ApiAuthentication
{
    /**
     * Verifying credentials that miss the user cache costs a password hash comparison, so the number of times one
     * caller may get them wrong is bounded. Callers that authenticate successfully never accumulate any of these.
     */
    private const int MAX_FAILED_ATTEMPTS = 60;

    private const int FAILED_ATTEMPTS_DECAY_SECONDS = 60;

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
            $throttleKey = $this->throttleKey($request);

            if (RateLimiter::tooManyAttempts($throttleKey, self::MAX_FAILED_ATTEMPTS)) {
                return $this->unauthenticated();
            }

            $result = $this->userService->loginAsUserFromAuthenticationHeader($request);

            if ($result !== BasicAuthenticationResult::Success) {
                // Every failure answers the same opaque 401, so the log line is the only place that says
                // whether the credentials were rejected or were never usable in the first place. A request
                // that carried no Authorization header at all is not logged: that is every URL scanner
                // that ever walks the API, and the group middleware runs before the route-level throttles,
                // so logging it would hand anyone an unthrottled way to flood the warning log. For the same
                // reason such a request costs nothing to answer and is not counted against the throttle.
                if ($result !== BasicAuthenticationResult::MissingHeader) {
                    RateLimiter::hit($throttleKey, self::FAILED_ATTEMPTS_DECAY_SECONDS);

                    $this->log->handleAuthenticationFailed($result->value);
                }

                return $this->unauthenticated();
            }

            RateLimiter::clear($throttleKey);
        }

        return $next($request);
    }

    /**
     * The username is part of the key so that one integration getting its credentials wrong cannot keep another
     * integration - or every other caller sharing its resolved IP - from authenticating.
     */
    private function throttleKey(Request $request): string
    {
        return sprintf(
            'api-authentication:%s|%s',
            $request->ip(),
            sha1(mb_strtolower((string)$request->getUser())),
        );
    }

    private function unauthenticated(): Response
    {
        return response()->json(['error' => __('exceptions.handler.unauthenticated')], StatusCode::UNAUTHORIZED);
    }
}
