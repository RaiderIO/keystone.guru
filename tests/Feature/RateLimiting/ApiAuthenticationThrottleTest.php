<?php

namespace Tests\Feature\RateLimiting;

use App\Http\Middleware\Api\ApiAuthentication;
use App\Http\Middleware\Api\ApiAuthenticationThrottle;
use App\Models\User;
use App\Service\User\UserServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use ReflectionClassConstant;
use Symfony\Component\HttpFoundation\Response;
use Teapot\StatusCode;
use Tests\TestCases\PublicTestCase;

/**
 * Verifying credentials that miss the user cache costs a password hash comparison, and the api middleware group
 * runs before any route-level throttle - so a dedicated middleware ahead of the authentication puts a bound on
 * how often one caller may present unusable credentials.
 */
#[Group('RateLimiting')]
#[Group('Api')]
final class ApiAuthenticationThrottleTest extends PublicTestCase
{
    private const string IP = '203.0.113.42';

    private const string USERNAME = 'someone@example.com';

    private const string PASSWORD = 'a-password-for-this-test';

    #[Test]
    public function apiMiddlewareGroup_givenTheStackTheRouterExecutes_throttlesBeforeAuthenticating(): void
    {
        // Arrange - the middleware groups are registered while the http kernel handles a request, not on boot
        $this->get(route('api.v1.combatlog.dungeon.index'));
        $route = app('router')->getRoutes()->getByName('api.v1.combatlog.dungeon.index');

        // Act - gatherRouteMiddleware() returns the stack sorted by the priority list, which is what actually runs
        $middleware = array_values(app('router')->gatherRouteMiddleware($route));

        // Assert - bounding the hash comparisons only works if the bound is read before they are made
        $throttleIndex       = array_search(ApiAuthenticationThrottle::class, $middleware, true);
        $authenticationIndex = array_search(ApiAuthentication::class, $middleware, true);

        $this->assertNotFalse($throttleIndex, 'The api middleware group must throttle the authentication');
        $this->assertNotFalse($authenticationIndex, 'The api middleware group must authenticate');
        $this->assertLessThan($authenticationIndex, $throttleIndex);
    }

    #[Test]
    public function handle_givenRepeatedRejectedCredentials_stopsPassingThemOn(): void
    {
        // Arrange - the authentication behind the middleware answers 401 for every attempt
        $maxAttempts     = $this->maxFailedAttempts();
        $timesReached    = 0;
        $middleware      = $this->middleware();
        $rejectingKernel = static function () use (&$timesReached): Response {
            ++$timesReached;

            return new Response(status: StatusCode::UNAUTHORIZED);
        };

        RateLimiter::clear($this->throttleKey());

        try {
            // Act
            $statusCodes = [];
            for ($attempt = 0; $attempt < $maxAttempts + 1; ++$attempt) {
                $statusCodes[] = $middleware->handle($this->request(), $rejectingKernel)->getStatusCode();
            }

            // Assert - the caller cannot tell the throttled answer from the rejected one
            $this->assertSame(array_fill(0, $maxAttempts + 1, StatusCode::UNAUTHORIZED), $statusCodes);
            $this->assertSame($maxAttempts, $timesReached);
        } finally {
            RateLimiter::clear($this->throttleKey());
        }
    }

    #[Test]
    public function handle_givenSuccessfulAuthentication_forgetsEarlierFailures(): void
    {
        // Arrange
        $middleware = $this->middleware();

        RateLimiter::clear($this->throttleKey());
        for ($attempt = 0; $attempt < $this->maxFailedAttempts() - 1; ++$attempt) {
            RateLimiter::hit($this->throttleKey(), 60);
        }

        try {
            // Act
            $response = $middleware->handle($this->request(), static fn() => new Response());

            // Assert
            $this->assertSame(StatusCode::OK, $response->getStatusCode());
            $this->assertSame(0, RateLimiter::attempts($this->throttleKey()));
        } finally {
            RateLimiter::clear($this->throttleKey());
        }
    }

    #[Test]
    public function handle_givenAlreadyVerifiedCredentials_isNotThrottled(): void
    {
        // Arrange - a full bucket, and a caller whose credentials a previous request already verified
        $userService = $this->createMockPublic(UserServiceInterface::class);
        $userService->method('hasVerifiedCredentialsCached')->willReturn(true);

        $middleware = new ApiAuthenticationThrottle($userService);

        RateLimiter::clear($this->throttleKey());
        for ($attempt = 0; $attempt < $this->maxFailedAttempts(); ++$attempt) {
            RateLimiter::hit($this->throttleKey(), 60);
        }

        try {
            // Act
            $response = $middleware->handle($this->request(), static fn() => new Response());

            // Assert - draining the bucket is what keeps the caller working while others keep failing
            $this->assertSame(StatusCode::OK, $response->getStatusCode());
            $this->assertSame(0, RateLimiter::attempts($this->throttleKey()));
        } finally {
            RateLimiter::clear($this->throttleKey());
        }
    }

    #[Test]
    public function handle_givenNoAuthorizationHeader_countsNothing(): void
    {
        // Arrange - answering a request without credentials is free, so it must not fill up the bucket
        $userService = $this->createMockPublic(UserServiceInterface::class);
        $userService->expects($this->never())->method('hasVerifiedCredentialsCached');

        $middleware = new ApiAuthenticationThrottle($userService);

        RateLimiter::clear($this->throttleKey(''));

        try {
            // Act
            $response = $middleware->handle(
                $this->request(withCredentials: false),
                static fn() => new Response(status: StatusCode::UNAUTHORIZED),
            );

            // Assert
            $this->assertSame(StatusCode::UNAUTHORIZED, $response->getStatusCode());
            $this->assertSame(0, RateLimiter::attempts($this->throttleKey('')));
        } finally {
            RateLimiter::clear($this->throttleKey(''));
        }
    }

    #[Test]
    public function apiRoute_givenAlreadyVerifiedCredentialsWhileTheBucketIsFull_stillAuthenticates(): void
    {
        // Arrange - the buckets are shared by everyone resolving to the same IP, so a caller whose credentials
        // are right must not be turned away by failures it did not produce
        $caller       = User::factory()->create(['password' => Hash::make(self::PASSWORD)]);
        $usernameKey  = sprintf('api-authentication:127.0.0.1|%s', sha1(mb_strtolower($caller->email)));
        $dungeonIndex = route('api.v1.combatlog.dungeon.index');
        $this->pretendNotRunningUnitTests();

        try {
            // The first request verifies the password and puts the caller in the user cache
            $this->get($dungeonIndex, $this->credentialsOf($caller->email, self::PASSWORD))->assertStatus(StatusCode::OK);

            $rejectedResponse = $this->get($dungeonIndex, $this->credentialsOf($caller->email, 'not-the-password'));

            // The counter is keyed off the credentials the request actually carried
            $this->assertSame(StatusCode::UNAUTHORIZED, $rejectedResponse->status());
            $this->assertSame(1, RateLimiter::attempts($usernameKey));

            for ($attempt = RateLimiter::attempts($usernameKey); $attempt < $this->maxFailedAttempts(); ++$attempt) {
                RateLimiter::hit($usernameKey, 60);
            }

            // Act
            $cachedResponse   = $this->get($dungeonIndex, $this->credentialsOf($caller->email, self::PASSWORD));
            $rejectedResponse = $this->get($dungeonIndex, $this->credentialsOf($caller->email, 'not-the-password'));

            // Assert
            $cachedResponse->assertStatus(StatusCode::OK);
            $rejectedResponse->assertStatus(StatusCode::UNAUTHORIZED);
            // The cached success emptied the bucket, so the one failure after it is all that is left
            $this->assertSame(1, RateLimiter::attempts($usernameKey));
        } finally {
            $this->pretendRunningUnitTests();
            RateLimiter::clear($usernameKey);
            $caller->delete();
        }
    }

    private function middleware(): ApiAuthenticationThrottle
    {
        $userService = $this->createMockPublic(UserServiceInterface::class);
        $userService->method('hasVerifiedCredentialsCached')->willReturn(false);

        return new ApiAuthenticationThrottle($userService);
    }

    /**
     * @return array<string, string>
     */
    private function credentialsOf(string $email, string $password): array
    {
        return ['Authorization' => sprintf('Basic %s', base64_encode(sprintf('%s:%s', $email, $password)))];
    }

    private function request(bool $withCredentials = true): Request
    {
        $server = ['REMOTE_ADDR' => self::IP];

        if ($withCredentials) {
            $server['PHP_AUTH_USER'] = self::USERNAME;
            $server['PHP_AUTH_PW']   = 'not-the-password';
        }

        return Request::create('/api/v1/dungeon', 'GET', server: $server);
    }

    private function throttleKey(?string $username = null): string
    {
        return sprintf('api-authentication:%s|%s', self::IP, sha1(mb_strtolower($username ?? self::USERNAME)));
    }

    private function maxFailedAttempts(): int
    {
        return (int)new ReflectionClassConstant(ApiAuthenticationThrottle::class, 'MAX_FAILED_ATTEMPTS_PER_USERNAME')->getValue();
    }

    /**
     * The authentication skips outright while the application knows it is running tests, which is what lets every
     * other feature test call the api without credentials.
     */
    private function pretendNotRunningUnitTests(): void
    {
        $this->app['env'] = 'local';
    }

    private function pretendRunningUnitTests(): void
    {
        $this->app['env'] = 'testing';
    }
}
