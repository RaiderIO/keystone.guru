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
     * caller may present unusable credentials for a given username is bounded. Credentials that are already in the
     * user cache are honoured before this is consulted, so a caller that gets its credentials right is never held
     * up by one that does not.
     */
    private const int MAX_FAILED_ATTEMPTS_PER_USERNAME = 60;

    /**
     * Credentials naming a user nobody registered never reach the hash comparison, but they do cost a user lookup,
     * so the total is bounded per IP too. Until #4536 lands, $request->ip() resolves to the load balancer rather
     * than to the visitor in production, which makes this a single site-wide bucket - hence the deliberately high
     * ceiling. The number wants revisiting once the real visitor IP is available here.
     */
    private const int MAX_FAILED_ATTEMPTS_PER_IP = 1200;

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
        // Credentials a previous request already verified are honoured before anything is counted or checked: the
        // throttle keys below are shared by everyone resolving to the same IP, so a full bucket must never be able
        // to keep a caller that has its credentials right out of the api.
        if (!app()->runningUnitTests() && !$this->userService->loginAsCachedUserFromAuthenticationHeader($request)) {
            $usernameThrottleKey = $this->usernameThrottleKey($request);
            $ipThrottleKey       = $this->ipThrottleKey($request);

            if (RateLimiter::tooManyAttempts($usernameThrottleKey, self::MAX_FAILED_ATTEMPTS_PER_USERNAME) ||
                RateLimiter::tooManyAttempts($ipThrottleKey, self::MAX_FAILED_ATTEMPTS_PER_IP)) {
                return $this->unauthenticated();
            }

            $result = $this->userService->loginAsUserFromAuthenticationHeader($request);

            if ($result !== BasicAuthenticationResult::Success) {
                // Every failure answers the same opaque 401, so the log line is the only place that says
                // whether the credentials were rejected or were never usable in the first place. A request
                // that carried no Authorization header at all is not logged: that is every URL scanner
                // that ever walks the API, and the group middleware runs before the route-level throttles,
                // so logging it would hand anyone an unthrottled way to flood the warning log. For the same
                // reason such a request costs nothing to answer and is not counted against the throttles.
                if ($result !== BasicAuthenticationResult::MissingHeader) {
                    RateLimiter::hit($usernameThrottleKey, self::FAILED_ATTEMPTS_DECAY_SECONDS);
                    RateLimiter::hit($ipThrottleKey, self::FAILED_ATTEMPTS_DECAY_SECONDS);

                    $this->log->handleAuthenticationFailed($result->value);
                }

                return $this->unauthenticated();
            }

            RateLimiter::clear($usernameThrottleKey);
        }

        return $next($request);
    }

    /**
     * The username is part of the key so that one integration getting its credentials wrong cannot keep another
     * integration - or every other caller sharing its resolved IP - from authenticating.
     */
    private function usernameThrottleKey(Request $request): string
    {
        return sprintf(
            'api-authentication:%s|%s',
            $request->ip(),
            sha1(mb_strtolower((string)$request->getUser())),
        );
    }

    private function ipThrottleKey(Request $request): string
    {
        return sprintf('api-authentication:%s', $request->ip());
    }

    private function unauthenticated(): Response
    {
        return response()->json(['error' => __('exceptions.handler.unauthenticated')], StatusCode::UNAUTHORIZED);
    }
}
