<?php

namespace Tests\Feature\App\Service\Cache;

use App\Service\Cache\CacheService;
use App\Service\Cache\Logging\CacheServiceLoggingInterface;
use App\Service\Cache\Redis\RedisServiceInterface;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use RedisException;
use Tests\TestCases\PublicTestCase;

/**
 * Covers #3914: CacheService::get() is called from TrustProxies on every production request (via
 * CloudflareService::getIpRanges()), so an uncaught RedisException there previously 500'd the whole
 * site on a transient Redis connection blip instead of degrading gracefully.
 */
#[Group('Cache')]
#[Group('CacheServiceRedisResilience')]
final class CacheServiceRedisResilienceTest extends PublicTestCase
{
    private function makeCacheService(CacheServiceLoggingInterface $log): CacheService
    {
        /** @var MockObject&RedisServiceInterface $redisService */
        $redisService = $this->createMockPublic(RedisServiceInterface::class);

        return new CacheService($redisService, $log);
    }

    #[Test]
    public function get_givenRedisConnectionFails_returnsNullInsteadOfThrowing(): void
    {
        // Arrange
        Cache::shouldReceive('get')->once()->andThrow(new RedisException('Connection timed out'));

        /** @var MockObject&CacheServiceLoggingInterface $log */
        $log = $this->createMockPublic(CacheServiceLoggingInterface::class);
        $log->expects($this->once())->method('getFailedRedisConnection')->with('some:key', $this->isInstanceOf(RedisException::class));

        $cacheService = $this->makeCacheService($log);

        // Act
        $result = $cacheService->get('some:key');

        // Assert
        $this->assertNull($result, 'A dropped Redis connection must degrade to a cache miss, not propagate');
    }

    #[Test]
    public function remember_givenRedisConnectionFailsOnRead_stillReturnsFreshlyComputedValue(): void
    {
        // Arrange
        Cache::shouldReceive('get')->once()->andThrow(new RedisException('Connection timed out'));
        Cache::shouldReceive('set')->once()->andReturn(true);

        /** @var MockObject&CacheServiceLoggingInterface $log */
        $log = $this->createMockPublic(CacheServiceLoggingInterface::class);
        $log->expects($this->once())->method('getFailedRedisConnection');

        $cacheService = $this->makeCacheService($log);
        $closureCalls = 0;

        // Act
        $result = $cacheService->remember('some:key', function () use (&$closureCalls) {
            $closureCalls++;

            return 'fresh value';
        });

        // Assert
        $this->assertSame('fresh value', $result);
        $this->assertSame(1, $closureCalls, 'A degraded read must still recompute the value once');
    }

    #[Test]
    public function remember_givenRedisConnectionFailsOnWrite_stillReturnsFreshlyComputedValue(): void
    {
        // Arrange
        Cache::shouldReceive('get')->once()->andReturn(null);
        Cache::shouldReceive('set')->once()->andThrow(new RedisException('Connection timed out'));

        /** @var MockObject&CacheServiceLoggingInterface $log */
        $log = $this->createMockPublic(CacheServiceLoggingInterface::class);
        $log->expects($this->once())->method('rememberFailedToSetCache')->with('some:key', $this->isInstanceOf(RedisException::class));

        $cacheService = $this->makeCacheService($log);
        $closureCalls = 0;

        // Act
        $result = $cacheService->remember('some:key', function () use (&$closureCalls) {
            $closureCalls++;

            return 'fresh value';
        });

        // Assert - even though the value could not be cached, the caller must still get its result
        // rather than the request 500ing (this is the same failure mode TrustProxies hit in #3914)
        $this->assertSame('fresh value', $result);
        $this->assertSame(1, $closureCalls);
    }
}
