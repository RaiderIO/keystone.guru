<?php

namespace App\Http\Middleware\Api;

use App\Logic\Utils\IpAddress;
use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Service\User\UserServiceInterface;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;
use Teapot\StatusCode;

/**
 * Bounds how often one caller may present credentials that the api authentication cannot resolve from its user
 * cache: each such attempt costs a password hash comparison, and the api middleware group runs before any
 * route-level throttle, so nothing else in the stack puts a ceiling on it.
 *
 * Two buckets: one per IP and account, sized like the web login's per-username lockout, and one per IP across
 * every account, sized like the web `login` limiter.
 *
 * Rejected bearer tokens get a bucket of their own per IP: each one costs a signature check and a warning log line,
 * and must not eat into a Basic-auth caller's budget.
 */
class ApiAuthenticationThrottle
{
    private const int MAX_FAILED_ATTEMPTS_PER_ACCOUNT = 5;

    private const int FAILED_ATTEMPTS_PER_ACCOUNT_DECAY_SECONDS = 60;

    private const int MAX_FAILED_ATTEMPTS_PER_IP = 120;

    private const int FAILED_ATTEMPTS_PER_IP_DECAY_SECONDS = 3600;

    private const int MAX_FAILED_BEARER_ATTEMPTS_PER_IP = 60;

    private const int FAILED_BEARER_ATTEMPTS_DECAY_SECONDS = 60;

    public function __construct(
        private readonly UserServiceInterface    $userService,
        private readonly UserRepositoryInterface $userRepository,
    ) {
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // A request that carries no credentials at all is every URL scanner that ever walks the api: it costs
        // nothing to answer, so it is not counted.
        if (!$request->hasHeader('Authorization')) {
            return $next($request);
        }

        if ($request->bearerToken() !== null) {
            return $this->handleBearer($request, $next);
        }

        $accountThrottleKey = $this->accountThrottleKey($request);
        $ipThrottleKey      = $this->ipThrottleKey($request);

        // Credentials a previous request already verified are honoured however full the buckets are: a caller that
        // keeps getting its credentials right therefore always gets through, however many failures anyone else
        // produced for that account or IP meanwhile.
        if ((RateLimiter::tooManyAttempts($accountThrottleKey, self::MAX_FAILED_ATTEMPTS_PER_ACCOUNT)
                || RateLimiter::tooManyAttempts($ipThrottleKey, self::MAX_FAILED_ATTEMPTS_PER_IP))
            && !$this->userService->hasVerifiedCredentialsCached($request)) {
            // The same opaque answer the authentication itself gives, so the caller cannot tell the two apart
            return response()->json(['error' => __('exceptions.handler.unauthenticated')], StatusCode::UNAUTHORIZED);
        }

        $response = $next($request);

        if ($response->getStatusCode() === StatusCode::UNAUTHORIZED) {
            RateLimiter::hit($accountThrottleKey, self::FAILED_ATTEMPTS_PER_ACCOUNT_DECAY_SECONDS);
            RateLimiter::hit($ipThrottleKey, self::FAILED_ATTEMPTS_PER_IP_DECAY_SECONDS);
        } else {
            // The IP bucket is left alone: one working account must not reset the ceiling for every other one
            RateLimiter::clear($accountThrottleKey);
        }

        return $response;
    }

    private function handleBearer(Request $request, Closure $next): Response
    {
        $throttleKey = sprintf('api-authentication-bearer:%s', $request->ip());

        if (RateLimiter::tooManyAttempts($throttleKey, self::MAX_FAILED_BEARER_ATTEMPTS_PER_IP)) {
            return response()->json(['error' => __('exceptions.handler.unauthenticated')], StatusCode::UNAUTHORIZED);
        }

        $response = $next($request);

        // Not cleared on success: one valid token must not reset the budget of the garbage sent next to it
        if ($response->getStatusCode() === StatusCode::UNAUTHORIZED) {
            RateLimiter::hit($throttleKey, self::FAILED_BEARER_ATTEMPTS_DECAY_SECONDS);
        }

        return $response;
    }

    /**
     * The account is part of the key so that one integration getting its credentials wrong cannot keep another
     * integration - or every other caller sharing its resolved IP - from authenticating. A username that resolves
     * to an account is keyed on that account, so every spelling the database considers equal shares one bucket.
     */
    private function accountThrottleKey(Request $request): string
    {
        $username = (string)$request->getUser();
        $userId   = $username === '' ? null : $this->userRepository->findIdByEmail($username);

        return sprintf(
            'api-authentication:%s|%s',
            IpAddress::rateLimitKey($request->ip()),
            $userId === null ? sha1(mb_strtolower(trim($username))) : sprintf('user:%d', $userId),
        );
    }

    private function ipThrottleKey(Request $request): string
    {
        return sprintf('api-authentication-ip:%s', IpAddress::rateLimitKey($request->ip()));
    }
}
