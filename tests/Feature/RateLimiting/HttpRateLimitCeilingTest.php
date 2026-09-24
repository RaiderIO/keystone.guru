<?php

namespace Tests\Feature\RateLimiting;

use App\Providers\AppServiceProvider;
use Illuminate\Cache\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use ReflectionProperty;
use Tests\TestCase;

/**
 * Each ceiling stays at or above the whole site's busiest hour, so only abuse reaches it, even behind
 * an address shared by many visitors.
 */
#[Group('RateLimiting')]
final class HttpRateLimitCeilingTest extends TestCase
{
    #[Test]
    #[DataProvider('limiterCeilingProvider')]
    public function limiter_givenAnonymousPost_allowsTheHourlyCeiling(string $limiterName, int $expectedMaxAttempts): void
    {
        // Arrange
        $this->overrideHttpRateLimit(null);

        try {
            // Act
            $limit = $this->resolveLimit($limiterName);

            // Assert
            $this->assertSame($expectedMaxAttempts, $limit->maxAttempts);
            $this->assertSame(3600, $limit->decaySeconds);
            $this->assertSame('203.0.113.10', $limit->key);
        } finally {
            $this->overrideHttpRateLimit(null);
        }
    }

    /**
     * @return array<string, array{string, int}>
     */
    public static function limiterCeilingProvider(): array
    {
        return [
            'create-dungeonroute' => ['create-dungeonroute', 100],
            'edit-dungeonroute'   => ['edit-dungeonroute', 1200],
            'create-tag'          => ['create-tag', 60],
            'create-collection'   => ['create-collection', 30],
            'create-team'         => ['create-team', 5],
            'create-reports'      => ['create-reports', 60],
            'create-user'         => ['create-user', 50],
            'login'               => ['login', 120],
            'reset-password'      => ['reset-password', 60],
            'store-metric'        => ['store-metric', 600],
            'search-dungeonroute' => ['search-dungeonroute', 600],
            'heatmap-data'        => ['heatmap-data', 600],
            'mdt-details'         => ['mdt-details', 300],
            'mdt-export'          => ['mdt-export', 600],
            'simulate'            => ['simulate', 120],
        ];
    }

    private function resolveLimit(string $name): Limit
    {
        $limiter = app(RateLimiter::class)->limiter($name);

        return $limiter(Request::create('/', 'POST', server: ['REMOTE_ADDR' => '203.0.113.10']));
    }

    private function overrideHttpRateLimit(?int $limit): void
    {
        new ReflectionProperty(AppServiceProvider::class, 'rateLimitOverrideHttp')->setValue(null, $limit);
    }
}
