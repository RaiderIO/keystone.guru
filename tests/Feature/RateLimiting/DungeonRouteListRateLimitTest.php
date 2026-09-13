<?php

namespace Tests\Feature\RateLimiting;

use App\Providers\AppServiceProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use ReflectionProperty;
use Tests\TestCases\PublicTestCase;

/**
 * The datatables route list is a heavy GET that takes no session, so it shares the search limiter of its siblings. The
 * request runs as a guest on purpose: the seeded admin is exempt from every limiter.
 */
#[Group('RateLimiting')]
#[Group('DungeonRoute')]
final class DungeonRouteListRateLimitTest extends PublicTestCase
{
    #[Test]
    public function get_givenTooManyRequestsFromAGuest_isRateLimited(): void
    {
        // Arrange - one request per hour is enough to prove the throttle is applied to the route
        $this->overrideHttpRateLimit(1);
        $query = http_build_query([
            'draw'    => 1,
            'start'   => 0,
            'length'  => 25,
            'columns' => [
                [
                    'data'       => 0,
                    'name'       => 'title',
                    'searchable' => 'true',
                    'orderable'  => 'true',
                    'search'     => ['value' => '', 'regex' => 'false'],
                ],
            ],
            'search' => ['value' => '', 'regex' => 'false'],
        ]);

        try {
            // Act
            $firstResponse  = $this->get(sprintf('/ajax/routes?%s', $query), ['X-Requested-With' => 'XMLHttpRequest']);
            $secondResponse = $this->get(sprintf('/ajax/routes?%s', $query), ['X-Requested-With' => 'XMLHttpRequest']);

            // Assert
            $firstResponse->assertOk();
            $secondResponse->assertStatus(429);
        } finally {
            $this->overrideHttpRateLimit(null);
        }
    }

    private function overrideHttpRateLimit(?int $limit): void
    {
        new ReflectionProperty(AppServiceProvider::class, 'rateLimitOverrideHttp')->setValue(null, $limit);
    }
}
