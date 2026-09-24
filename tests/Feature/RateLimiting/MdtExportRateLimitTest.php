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
 * The limiter buckets anonymous callers by $request->ip(), so its number is a per-visitor ceiling only
 * while that resolves to the visitor. Should it collapse to a proxy address again, every visitor shares
 * one bucket, and 600 an hour sits well below the site-wide peak of ~1,650 exports an hour.
 */
#[Group('RateLimiting')]
final class MdtExportRateLimitTest extends TestCase
{
    #[Test]
    public function mdtExportLimiter_givenAnonymousRequest_allowsThePerVisitorHourlyCeiling(): void
    {
        // Arrange
        $this->overrideHttpRateLimit(null);

        try {
            // Act
            $limit = $this->resolveLimit('mdt-export');

            // Assert
            $this->assertSame(600, $limit->maxAttempts);
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
