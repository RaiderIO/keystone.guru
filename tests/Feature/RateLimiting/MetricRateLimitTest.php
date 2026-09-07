<?php

namespace Tests\Feature\RateLimiting;

use App\Models\Metrics\Metric;
use App\Providers\AppServiceProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use ReflectionProperty;
use Tests\TestCases\PublicTestCase;

/**
 * The endpoint takes no session and writes a row per call, so it is bounded for the callers that are not exempt -
 * which is every visitor the front-end sends here.
 */
#[Group('RateLimiting')]
#[Group('Metric')]
final class MetricRateLimitTest extends PublicTestCase
{
    #[Test]
    public function store_givenTooManyRequestsFromAGuest_isRateLimited(): void
    {
        // Arrange - one request per hour is enough to prove the throttle is applied to the route
        $maxMetricId = (int)Metric::query()->max('id');
        $this->overrideHttpRateLimit(1);

        $payload = [
            'model_id'    => null,
            'model_class' => null,
            'category'    => Metric::CATEGORY_DUNGEON_ROUTE_MDT_COPY,
            'tag'         => Metric::TAG_MDT_COPY_VIEW,
            'value'       => 1,
        ];

        try {
            // Act
            $firstResponse  = $this->post('/ajax/metric', $payload, ['X-Requested-With' => 'XMLHttpRequest']);
            $secondResponse = $this->post('/ajax/metric', $payload, ['X-Requested-With' => 'XMLHttpRequest']);

            // Assert
            $firstResponse->assertNoContent();
            $secondResponse->assertStatus(429);
        } finally {
            $this->overrideHttpRateLimit(null);
            Metric::query()->where('id', '>', $maxMetricId)->delete();
        }
    }

    private function overrideHttpRateLimit(?int $limit): void
    {
        new ReflectionProperty(AppServiceProvider::class, 'rateLimitOverrideHttp')->setValue(null, $limit);
    }
}
