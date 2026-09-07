<?php

namespace Tests\Feature\RateLimiting;

use App\Providers\AppServiceProvider;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use ReflectionProperty;
use Tests\TestCase;

/**
 * The mdt-export limiter buckets anonymous callers by $request->ip(), which in production resolves
 * to the load balancer rather than the visitor (#4536) - every anonymous visitor behind one ALB
 * node therefore shares a single bucket. That makes the configured number a site-wide ceiling, not
 * a per-visitor one, so lowering it to a per-visitor-looking value takes the endpoint down for
 * everyone (#4535, Sentry PHP-LARAVEL-S6).
 */
#[Group('RateLimiting')]
final class MdtExportRateLimitTest extends TestCase
{
    #[Test]
    public function mdtExportLimiter_givenAnonymousRequest_allowsTheSiteWideHourlyCeiling(): void
    {
        // Arrange
        $this->overrideHttpRateLimit(null);

        try {
            // Act
            $limit = $this->resolveLimit('mdt-export');

            // Assert
            $this->assertSame(1200, $limit->maxAttempts);
            $this->assertSame(3600, $limit->decaySeconds);
        } finally {
            $this->overrideHttpRateLimit(null);
        }
    }

    #[Test]
    public function mdtExportLimiter_givenAnonymousRequest_isNotStricterThanMdtDetails(): void
    {
        // Arrange
        $this->overrideHttpRateLimit(null);

        try {
            // Act
            $mdtExport  = $this->resolveLimit('mdt-export');
            $mdtDetails = $this->resolveLimit('mdt-details');

            // Assert
            $this->assertGreaterThanOrEqual($mdtDetails->maxAttempts, $mdtExport->maxAttempts);
        } finally {
            $this->overrideHttpRateLimit(null);
        }
    }

    private function resolveLimit(string $name): \Illuminate\Cache\RateLimiting\Limit
    {
        $limiter = app(RateLimiter::class)->limiter($name);

        return $limiter(Request::create('/', 'GET', server: ['REMOTE_ADDR' => '203.0.113.10']));
    }

    private function overrideHttpRateLimit(?int $limit): void
    {
        new ReflectionProperty(AppServiceProvider::class, 'rateLimitOverrideHttp')->setValue(null, $limit);
    }
}
