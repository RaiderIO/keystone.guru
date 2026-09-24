<?php

namespace Tests\Feature\RateLimiting;

use App\Providers\AppServiceProvider;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use ReflectionProperty;
use Tests\TestCases\PublicTestCase;

/**
 * The map editor and tag writes accept requests without a session. The throttle runs before route model binding, so a
 * route key that does not exist still spends an attempt - which keeps these tests free of fixtures. The requests run as
 * a guest on purpose: the seeded admin is exempt from every limiter.
 */
#[Group('RateLimiting')]
final class MapEditorRateLimitTest extends PublicTestCase
{
    #[Test]
    #[DataProvider('throttledWriteProvider')]
    public function write_givenTooManyRequestsFromAGuest_isRateLimited(string $method, string $uri): void
    {
        // Arrange - one request per hour is enough to prove the throttle is applied to the route
        $this->overrideHttpRateLimit(1);

        try {
            // Act
            $firstResponse  = $this->json($method, $uri);
            $secondResponse = $this->json($method, $uri);

            // Assert
            $this->assertNotSame(429, $firstResponse->getStatusCode());
            $secondResponse->assertStatus(429);
        } finally {
            $this->overrideHttpRateLimit(null);
        }
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function throttledWriteProvider(): array
    {
        return [
            'brushline create' => ['POST', '/ajax/doesnotexist/brushline'],
            'killzone mass'    => ['PUT', '/ajax/doesnotexist/killzone/mass'],
            'killzone delete'  => ['DELETE', '/ajax/doesnotexist/killzone/1'],
            'mapicon update'   => ['PUT', '/ajax/doesnotexist/mapicon/1'],
            'pridefulenemy'    => ['POST', '/ajax/doesnotexist/pridefulenemy/1'],
            'path create'      => ['POST', '/ajax/doesnotexist/path'],
            'arrow delete'     => ['DELETE', '/ajax/doesnotexist/arrow/1'],
            'raidmarker'       => ['POST', '/ajax/doesnotexist/raidmarker/1'],
            'tag delete'       => ['DELETE', '/ajax/tag/1'],
            'tag update all'   => ['PUT', '/ajax/tag/1/all'],
            'tag delete all'   => ['DELETE', '/ajax/tag/1/all'],
        ];
    }

    #[Test]
    public function show_givenManyRequestsFromAGuest_isNotRateLimited(): void
    {
        // Arrange
        $this->overrideHttpRateLimit(1);

        try {
            // Act
            $firstResponse  = $this->json('GET', '/ajax/doesnotexist/path/1');
            $secondResponse = $this->json('GET', '/ajax/doesnotexist/path/1');

            // Assert
            $this->assertNotSame(429, $firstResponse->getStatusCode());
            $this->assertNotSame(429, $secondResponse->getStatusCode());
        } finally {
            $this->overrideHttpRateLimit(null);
        }
    }

    private function overrideHttpRateLimit(?int $limit): void
    {
        new ReflectionProperty(AppServiceProvider::class, 'rateLimitOverrideHttp')->setValue(null, $limit);
    }
}
