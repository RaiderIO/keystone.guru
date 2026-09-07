<?php

namespace Tests\Feature\RateLimiting;

use App\Http\Middleware\Api\ApiAuthentication;
use App\Http\Middleware\Api\Logging\ApiAuthenticationLoggingInterface;
use App\Service\User\Dtos\BasicAuthenticationResult;
use App\Service\User\UserServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use ReflectionClassConstant;
use Symfony\Component\HttpFoundation\Response;
use Teapot\StatusCode;
use Tests\TestCases\PublicTestCase;

/**
 * Verifying credentials that miss the user cache costs a password hash comparison, and the api middleware group
 * runs before any route-level throttle - so the bound on how often one caller may present unusable credentials
 * lives inside the middleware itself.
 */
#[Group('RateLimiting')]
#[Group('Api')]
final class ApiAuthenticationRateLimitTest extends PublicTestCase
{
    private const string IP = '203.0.113.42';

    private const string USERNAME = 'someone@example.com';

    #[Test]
    public function handle_givenRepeatedUnusableCredentials_stopsVerifyingThem(): void
    {
        // Arrange
        $maxAttempts = $this->maxFailedAttempts();
        $userService = $this->createMockPublic(UserServiceInterface::class);
        $userService->expects($this->exactly($maxAttempts))
            ->method('loginAsUserFromAuthenticationHeader')
            ->willReturn(BasicAuthenticationResult::CredentialsRejected);

        $middleware = new ApiAuthentication($userService, $this->createMockPublic(ApiAuthenticationLoggingInterface::class));

        RateLimiter::clear($this->throttleKey());
        $this->pretendNotRunningUnitTests();

        try {
            // Act
            $statusCodes = [];
            for ($attempt = 0; $attempt < $maxAttempts + 1; ++$attempt) {
                $statusCodes[] = $middleware->handle($this->request(), static fn() => new Response())->getStatusCode();
            }

            // Assert - the caller cannot tell the throttled answer from the rejected one
            $this->assertSame(array_fill(0, $maxAttempts + 1, StatusCode::UNAUTHORIZED), $statusCodes);
        } finally {
            $this->pretendRunningUnitTests();
            RateLimiter::clear($this->throttleKey());
        }
    }

    #[Test]
    public function handle_givenSuccessfulAuthentication_forgetsEarlierFailures(): void
    {
        // Arrange
        $userService = $this->createMockPublic(UserServiceInterface::class);
        $userService->expects($this->once())
            ->method('loginAsUserFromAuthenticationHeader')
            ->willReturn(BasicAuthenticationResult::Success);

        $middleware = new ApiAuthentication($userService, $this->createMockPublic(ApiAuthenticationLoggingInterface::class));

        RateLimiter::clear($this->throttleKey());
        for ($attempt = 0; $attempt < $this->maxFailedAttempts() - 1; ++$attempt) {
            RateLimiter::hit($this->throttleKey(), 60);
        }
        $this->pretendNotRunningUnitTests();

        try {
            // Act
            $response = $middleware->handle($this->request(), static fn() => new Response());

            // Assert
            $this->assertSame(StatusCode::OK, $response->getStatusCode());
            $this->assertSame(0, RateLimiter::attempts($this->throttleKey()));
        } finally {
            $this->pretendRunningUnitTests();
            RateLimiter::clear($this->throttleKey());
        }
    }

    #[Test]
    public function handle_givenNoAuthorizationHeader_countsNothing(): void
    {
        // Arrange - answering a request without credentials is free, so it must not fill up the bucket
        $userService = $this->createMockPublic(UserServiceInterface::class);
        $userService->expects($this->once())
            ->method('loginAsUserFromAuthenticationHeader')
            ->willReturn(BasicAuthenticationResult::MissingHeader);

        $log = $this->createMockPublic(ApiAuthenticationLoggingInterface::class);
        $log->expects($this->never())->method('handleAuthenticationFailed');

        $middleware = new ApiAuthentication($userService, $log);

        RateLimiter::clear($this->throttleKey(''));
        $this->pretendNotRunningUnitTests();

        try {
            // Act
            $response = $middleware->handle($this->request(withCredentials: false), static fn() => new Response());

            // Assert
            $this->assertSame(StatusCode::UNAUTHORIZED, $response->getStatusCode());
            $this->assertSame(0, RateLimiter::attempts($this->throttleKey('')));
        } finally {
            $this->pretendRunningUnitTests();
            RateLimiter::clear($this->throttleKey(''));
        }
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
        return (int)new ReflectionClassConstant(ApiAuthentication::class, 'MAX_FAILED_ATTEMPTS')->getValue();
    }

    /**
     * The middleware skips authentication outright while the application knows it is running tests, which is what
     * lets every other feature test call the api without credentials.
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
