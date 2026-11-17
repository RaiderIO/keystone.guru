<?php

namespace Tests\Feature\RateLimiting;

use App\Models\Laratrust\Role;
use App\Models\User;
use App\Providers\AppServiceProvider;
use App\Service\Patreon\PatreonServiceInterface;
use Illuminate\Support\Facades\RateLimiter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use ReflectionProperty;
use Tests\TestCases\PublicTestCase;

/**
 * The OAuth sign-in routes and the Patreon link route each lead to an outbound call to the provider, so they share a
 * named limiter.
 */
#[Group('RateLimiting')]
#[Group('OAuth')]
final class OAuthRateLimitTest extends PublicTestCase
{
    #[Test]
    #[DataProvider('route_givenAnOAuthRoute_hasTheOAuthLimiter_dataProvider')]
    public function route_givenAnOAuthRoute_hasTheOAuthLimiter(string $routeName): void
    {
        // Arrange
        $route = app('router')->getRoutes()->getByName($routeName);

        // Act
        $middleware = $route->gatherMiddleware();

        // Assert
        $this->assertContains('throttle:oauth', $middleware);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function route_givenAnOAuthRoute_hasTheOAuthLimiter_dataProvider(): array
    {
        return [
            'google redirect'    => ['login.google'],
            'google callback'    => ['login.google.callback'],
            'battlenet redirect' => ['login.battlenet'],
            'battlenet callback' => ['login.battlenet.callback'],
            'discord redirect'   => ['login.discord'],
            'discord callback'   => ['login.discord.callback'],
            'patreon link'       => ['patreon.link'],
        ];
    }

    #[Test]
    public function loginGoogle_givenTooManyRequestsFromAGuest_isRateLimited(): void
    {
        // Arrange - one request per hour is enough to prove the limiter is enforced
        $this->overrideHttpRateLimit(1);
        $this->clearGuestBucket();

        try {
            // Act
            $firstResponse  = $this->get(route('login.google'));
            $secondResponse = $this->get(route('login.google'));

            // Assert
            $firstResponse->assertRedirect();
            $secondResponse->assertStatus(429);
        } finally {
            $this->overrideHttpRateLimit(null);
            $this->clearGuestBucket();
        }
    }

    #[Test]
    public function patreonLink_givenTooManyRequestsFromAUser_isRateLimited(): void
    {
        // Arrange - a plain user, the internal roles are exempt from the limiter
        $user = User::factory()->create();
        $user->addRole(Role::firstWhere('name', Role::ROLE_USER));

        $patreonService = $this->createMockPublic(PatreonServiceInterface::class);
        $patreonService->expects($this->never())->method('linkToUserAccount');
        app()->instance(PatreonServiceInterface::class, $patreonService);

        $this->overrideHttpRateLimit(1);

        try {
            // Act
            $firstResponse = $this->actingAs($user)
                ->withSession(['_token' => 'test-csrf-token'])
                ->get(route('patreon.link', ['state' => 'some-other-token', 'code' => 'some-code']));
            $secondResponse = $this->actingAs($user)
                ->withSession(['_token' => 'test-csrf-token'])
                ->get(route('patreon.link', ['state' => 'some-other-token', 'code' => 'some-code']));

            // Assert
            $firstResponse->assertRedirect(route('profile.edit', ['#patreon']));
            $secondResponse->assertStatus(429);
        } finally {
            $this->overrideHttpRateLimit(null);
            RateLimiter::clear(md5(sprintf('oauth%s', $user->id)));
            $user->delete();
        }
    }

    #[Test]
    public function patreonLink_givenAGuest_redirectsToLogin(): void
    {
        // Arrange
        $patreonService = $this->createMockPublic(PatreonServiceInterface::class);
        $patreonService->expects($this->never())->method('linkToUserAccount');
        app()->instance(PatreonServiceInterface::class, $patreonService);

        // Act
        $response = $this->withSession(['_token' => 'test-csrf-token'])
            ->get(route('patreon.link', ['state' => 'test-csrf-token', 'code' => 'some-code']));

        // Assert
        $response->assertRedirect(route('login'));
    }

    private function clearGuestBucket(): void
    {
        RateLimiter::clear(md5('oauth127.0.0.1'));
    }

    private function overrideHttpRateLimit(?int $limit): void
    {
        new ReflectionProperty(AppServiceProvider::class, 'rateLimitOverrideHttp')->setValue(null, $limit);
    }
}
