<?php

namespace Tests\Feature\RateLimiting;

use App\Http\Middleware\Api\ApiAuthentication;
use App\Providers\AppServiceProvider;
use Illuminate\Cache\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use ReflectionProperty;
use Teapot\StatusCode;
use Tests\TestCases\PublicTestCase;

#[Group('RateLimiting')]
#[Group('Api')]
final class ApiGroupRateLimitTest extends PublicTestCase
{
    #[Test]
    public function apiMiddlewareGroup_givenTheApplicationConfiguration_throttlesEveryApiRoute(): void
    {
        // Arrange - the middleware groups are registered while the http kernel handles a request, not on boot
        $this->get(route('api.v1.combatlog.dungeon.index'));

        // Act
        $group = array_values(app('router')->getMiddlewareGroups()['api']);

        // Assert - after the authentication middleware, so the limiter sees the authenticated user
        $this->assertContains('throttle:api-general', $group);
        $this->assertGreaterThan(
            array_search(ApiAuthentication::class, $group, true),
            array_search('throttle:api-general', $group, true),
        );
    }

    #[Test]
    public function apiGeneralLimiter_givenAnonymousRequest_allowsTheConfiguredPerMinuteCeiling(): void
    {
        // Arrange
        $this->overrideApiRateLimit(null);

        // Act
        $limiter = app(RateLimiter::class)->limiter('api-general');
        /** @var Limit $limit */
        $limit = $limiter(Request::create('/api/v1/dungeon', 'GET', server: ['REMOTE_ADDR' => '203.0.113.10']));

        // Assert
        $this->assertSame(900, $limit->maxAttempts);
        $this->assertSame(60, $limit->decaySeconds);
    }

    #[Test]
    public function apiRoute_givenTooManyRequests_isRateLimited(): void
    {
        // Arrange - one request per minute is enough to prove the group throttle is actually applied
        $this->overrideApiRateLimit(1);

        try {
            // Act
            $firstResponse  = $this->get(route('api.v1.combatlog.dungeon.index'));
            $secondResponse = $this->get(route('api.v1.combatlog.dungeon.index'));

            // Assert
            $firstResponse->assertStatus(StatusCode::OK);
            $secondResponse->assertStatus(429);
        } finally {
            $this->overrideApiRateLimit(null);
        }
    }

    private function overrideApiRateLimit(?int $limit): void
    {
        new ReflectionProperty(AppServiceProvider::class, 'rateLimitOverridePerMinuteApi')->setValue(null, $limit);
    }
}
