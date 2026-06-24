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
 * Guards the clearIdleKeys regex against deleting Laravel session keys. Session ids are 40-char alphanumeric
 * strings stored with the same redis prefix as Model Cache keys (40-char sha1 hashes); a too-broad first
 * segment used to match and delete active sessions, logging idle users out.
 */
#[Group('Cache')]
#[Group('CacheServiceClearIdleKeys')]
final class CacheServiceClearIdleKeysTest extends PublicTestCase
{
    private const int IDLE_THRESHOLD_SECONDS = 900;

    private function prefix(): string
    {
        return config('database.redis.options.prefix') . config('cache.prefix');
    }

    /**
     * Runs clearIdleKeys against a CacheService whose Redis layer is fully mocked, so no real keys are touched.
     * SCAN returns the supplied keys once, OBJECT idletime reports every key as idle past the threshold, and
     * DEL is captured. Returns the bare list of keys that clearIdleKeys asked Redis to delete.
     *
     * @param  array<int, string> $keysOnEachConnection
     * @return array<int, string>
     */
    private function runClearIdleKeysAndCaptureDeletes(array $keysOnEachConnection): array
    {
        $deletedKeys = [];

        /** @var MockObject&RedisServiceInterface $redisService */
        $redisService = $this->createMockPublic(RedisServiceInterface::class);
        $redisService->method('rawCommand')->willReturnCallback(
            function (Connection $redis, string $command, ...$params) use (&$deletedKeys, $keysOnEachConnection): mixed {
                return match ($command) {
                    // [cursor, keys] - cursor 0 ends the SCAN loop after one iteration.
                    'SCAN' => ['0', $keysOnEachConnection],
                    // Report every key as idle well past any threshold so the idle check never shields a match.
                    'OBJECT' => self::IDLE_THRESHOLD_SECONDS + 1,
                    'DEL'    => (static function () use (&$deletedKeys, $params): int {
                        foreach ($params as $key) {
                            $deletedKeys[] = $key;
                        }

                        return count($params);
                    })(),
                    default => null,
                };
            },
        );

        /** @var MockObject&CacheServiceLoggingInterface $log */
        $log = $this->createMockPublic(CacheServiceLoggingInterface::class);

        $cacheService = new CacheService($redisService, $log);
        $cacheService->clearIdleKeys(self::IDLE_THRESHOLD_SECONDS);

        return $deletedKeys;
    }

    #[Test]
    public function clearIdleKeys_givenIdleSessionKey_doesNotDeleteIt(): void
    {
        // Arrange - a realistic 40-char alphanumeric Laravel session id (contains uppercase, so it is not hex).
        $sessionKey = $this->prefix() . '5ZZdBLgOMTm4TS9EVu0FL4JSEPOyJ4wSD03K7jcE';

        // Act
        $deletedKeys = $this->runClearIdleKeysAndCaptureDeletes([$sessionKey]);

        // Assert
        $this->assertNotContains($sessionKey, $deletedKeys, 'An idle session key must never be deleted by clearIdleKeys');
    }

    #[Test]
    public function clearIdleKeys_givenIdleModelCacheKey_deletesIt(): void
    {
        // Arrange - a Model Cache key: a sha1 hash, optionally chained with a second sha1 hash.
        $modelCacheKey = $this->prefix() . '65aa219314bb8283edd4a0ac5d83931692ea0bba:129d1d5ea35b2697e76199983478a8a1e2d916a9';

        // Act
        $deletedKeys = $this->runClearIdleKeysAndCaptureDeletes([$modelCacheKey]);

        // Assert
        $this->assertContains($modelCacheKey, $deletedKeys, 'An idle Model Cache key should still be cleaned up by clearIdleKeys');
    }
}
