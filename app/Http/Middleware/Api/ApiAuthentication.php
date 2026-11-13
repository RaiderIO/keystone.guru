<?php

namespace App\Http\Middleware\Api;

use App\Http\Middleware\Api\Logging\ApiAuthenticationLoggingInterface;
use App\Models\User;
use App\Service\User\Dtos\BasicAuthenticationResult;
use App\Service\User\UserServiceInterface;
use Carbon\Carbon;
use Carbon\CarbonInterval;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;
use Teapot\StatusCode;

class ApiAuthentication
{
    /** The day Basic authentication was deprecated in favour of OAuth bearer tokens */
    private const string BASIC_AUTH_DEPRECATED_AT = '2026-11-12';

    private const string CACHE_KEY_BASIC_AUTH_LOGGED = 'api_basic_auth_deprecated_logged:%d:%s';

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
        // Tests acting as a user without sending credentials keep bypassing authentication; a test that sends an
        // Authorization header gets the real thing, so both schemes stay testable end to end
        if (app()->runningUnitTests() && !$request->hasHeader('Authorization')) {
            return $next($request);
        }

        if ($request->bearerToken() !== null) {
            return $this->handleBearer($request, $next);
        }

        return $this->handleBasic($request, $next);
    }

    private function handleBearer(Request $request, Closure $next): Response
    {
        /** @var User|null $user */
        $user = Auth::guard('api')->user();

        if ($user === null) {
            $this->log->handleAuthenticationFailed('bearer_rejected');

            return $this->unauthorized();
        }

        // Policies, the api rate limiters and Laratrust all read the default guard
        Auth::setUser($user);

        return $next($request);
    }

    private function handleBasic(Request $request, Closure $next): Response
    {
        $result = $this->userService->loginAsUserFromAuthenticationHeader($request);

        if ($result !== BasicAuthenticationResult::Success) {
            // Every failure answers the same opaque 401, so the log line is the only place that says
            // whether the credentials were rejected or were never usable in the first place. A request
            // that carried no Authorization header at all is not logged: that is every URL scanner
            // that ever walks the API, and the group middleware runs before the route-level throttles,
            // so logging it would hand anyone an unthrottled way to flood the warning log.
            if ($result !== BasicAuthenticationResult::MissingHeader) {
                $this->log->handleAuthenticationFailed($result->value);
            }

            return $this->unauthorized();
        }

        // Once per user per day: enough to know who still has to migrate before the sunset, without a log line on
        // every request of a high-volume integration
        $userId = (int)Auth::id();
        if (Cache::add(sprintf(self::CACHE_KEY_BASIC_AUTH_LOGGED, $userId, now()->toDateString()), true, CarbonInterval::day())) {
            $this->log->handleBasicAuthenticationDeprecated($userId);
        }

        $response = $next($request);

        // RFC 9745 (Deprecation) and RFC 8594 (Sunset)
        $response->headers->set('Deprecation', sprintf('@%d', Carbon::parse(self::BASIC_AUTH_DEPRECATED_AT)->startOfDay()->getTimestamp()));
        $response->headers->set('Sunset', Carbon::parse(config('keystoneguru.api.authentication.basic_auth_sunset'))->startOfDay()->toRfc7231String());
        $response->headers->set('Link', sprintf('<%s>; rel="deprecation"; type="text/html"', route('l5-swagger.public.api')));

        return $response;
    }

    private function unauthorized(): Response
    {
        return response()->json(['error' => __('exceptions.handler.unauthenticated')], StatusCode::UNAUTHORIZED);
    }
}
