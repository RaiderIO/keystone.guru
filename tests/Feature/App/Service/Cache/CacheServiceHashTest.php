<?php

namespace Tests\Feature\App\Service\Cache;

use App\Service\Cache\CacheService;
use App\Service\Cache\Logging\CacheServiceLoggingInterface;
use App\Service\Cache\Redis\RedisServiceInterface;
use Illuminate\Redis\Connections\Connection;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCases\PublicTestCase;

/**
 * Covers the Redis-hash card cache: every card variant of a route lives as a field in a single hash keyed
 * dungeonroute_card:{id}, so dropping a route's caches is a single bounded DEL regardless of variant count.
 */
#[Group('Cache')]
#[Group('CacheServiceHash')]
final class CacheServiceHashTest extends PublicTestCase
{
    private function prefix(): string
    {
        return config('database.redis.options.prefix');
    }

    /**
     * Builds a CacheService whose Redis layer is fully mocked. Every rawCommand call is recorded, and the
     * HGET response is configurable so a hit and a miss can both be exercised without touching real Redis.
     *
     * @param  array<int, array{command: string, params: array<int, mixed>}> $recordedCommands
     * @return CacheService
     */
    private function makeCacheService(mixed $hgetResponse, array &$recordedCommands): CacheService
    {
        /** @var MockObject&RedisServiceInterface $redisService */
        $redisService = $this->createMockPublic(RedisServiceInterface::class);
        $redisService->method('rawCommand')->willReturnCallback(
            function (Connection $redis, string $command, ...$params) use ($hgetResponse, &$recordedCommands): mixed {
                $recordedCommands[] = ['command' => $command, 'params' => $params];

                return match ($command) {
                    'HGET'          => $hgetResponse,
                    'HSET'          => 1,
                    'EXPIRE', 'DEL' => 1,
                    default         => null,
                };
            },
        );

        /** @var MockObject&CacheServiceLoggingInterface $log */
        $log = $this->createMockPublic(CacheServiceLoggingInterface::class);

        return new CacheService($redisService, $log);
    }

    /**
     * @param  array<int, array{command: string, params: array<int, mixed>}> $recordedCommands
     * @return array<int, array<int, mixed>>
     */
    private function paramsForCommand(array $recordedCommands, string $command): array
    {
        return array_values(
            array_map(
                static fn(array $recorded): array => $recorded['params'],
                array_filter($recordedCommands, static fn(array $recorded): bool => $recorded['command'] === $command),
            ),
        );
    }

    #[Test]
    public function rememberInHash_givenCacheMiss_setsHashFieldAndReturnsClosureValue(): void
    {
        // Arrange
        $recordedCommands = [];
        // HGET returns false: phpredis reports a missing hash field as false.
        $cacheService = $this->makeCacheService(false, $recordedCommands);
        $closureCalls = 0;

        // Act
        $result = $cacheService->rememberInHash(
            'dungeonroute_card:123',
            'vertical:en_US_0_1_0',
            function () use (&$closureCalls): string {
                $closureCalls++;

                return '<div>card</div>';
            },
            '1 hour',
        );

        // Assert
        $this->assertSame('<div>card</div>', $result, 'The resolved value must be returned on a cache miss');
        $this->assertSame(1, $closureCalls, 'The closure must be called exactly once on a cache miss');

        $hsetCalls = $this->paramsForCommand($recordedCommands, 'HSET');
        $this->assertCount(1, $hsetCalls, 'A cache miss must write the field once via HSET');
        $this->assertSame($this->prefix() . 'dungeonroute_card:123', $hsetCalls[0][0], 'HSET must target the prefixed hash key');
        $this->assertSame('vertical:en_US_0_1_0', $hsetCalls[0][1], 'HSET must target the requested field');
        $this->assertSame('<div>card</div>', unserialize($hsetCalls[0][2]), 'HSET must store the serialized value');

        $expireCalls = $this->paramsForCommand($recordedCommands, 'EXPIRE');
        $this->assertCount(1, $expireCalls, 'A cache miss with a TTL must refresh the hash TTL via EXPIRE');
        $this->assertSame($this->prefix() . 'dungeonroute_card:123', $expireCalls[0][0], 'EXPIRE must target the prefixed hash key');
        $this->assertSame((string)(60 * 60), $expireCalls[0][1], 'A "1 hour" TTL must expire the hash after 3600 seconds');
    }

    #[Test]
    public function rememberInHash_givenCacheHit_returnsCachedValueWithoutRunningClosure(): void
    {
        // Arrange
        $recordedCommands = [];
        $cacheService     = $this->makeCacheService(serialize('<div>cached</div>'), $recordedCommands);
        $closureCalls     = 0;

        // Act
        $result = $cacheService->rememberInHash(
            'dungeonroute_card:123',
            'vertical:en_US_0_1_0',
            function () use (&$closureCalls): string {
                $closureCalls++;

                return '<div>fresh</div>';
            },
            '1 hour',
        );

        // Assert
        $this->assertSame('<div>cached</div>', $result, 'A cache hit must return the cached value');
        $this->assertSame(0, $closureCalls, 'The closure must not be called on a cache hit');
        $this->assertCount(0, $this->paramsForCommand($recordedCommands, 'HSET'), 'A cache hit must not write anything');
        $this->assertCount(0, $this->paramsForCommand($recordedCommands, 'EXPIRE'), 'A cache hit must not touch the TTL');
    }

    #[Test]
    public function dropHashCache_givenRouteKey_issuesSingleDel(): void
    {
        // Arrange
        $recordedCommands = [];
        $cacheService     = $this->makeCacheService(false, $recordedCommands);

        // Act
        $cacheService->dropHashCache('dungeonroute_card:123');

        // Assert - dropping every card variant of a route is exactly one DEL, regardless of variant count.
        $delCalls = $this->paramsForCommand($recordedCommands, 'DEL');
        $this->assertCount(1, $delCalls, 'Dropping a route hash must issue exactly one DEL');
        $this->assertSame($this->prefix() . 'dungeonroute_card:123', $delCalls[0][0], 'DEL must target the prefixed hash key');
    }
}
