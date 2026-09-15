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

    // The presence-key sweep hardcodes an 86400s idle threshold regardless of the seconds argument, so the
    // mocked OBJECT idletime response must clear that too.
    private const int PRESENCE_IDLE_TIME_SECONDS = 86400 + 1;

    private function prefix(): string
    {
        return config('database.redis.options.prefix') . config('cache.prefix');
    }

    /**
     * Runs clearIdleKeys against a CacheService whose Redis layer is fully mocked, so no real keys are touched.
     * SCAN returns the supplied keys once per connection it is called on, OBJECT idletime reports every key as
     * idle past the threshold, and DEL/SCAN calls are captured.
     *
     * @param  array<int, string>                                                                                                 $keysOnEachConnection
     * @return array{deletedKeys: array<int, string>, scanCalls: array<int, array{connection: string, args: array<int, string>}>}
     */
    private function runClearIdleKeysAndCaptureCalls(array $keysOnEachConnection): array
    {
        $deletedKeys = [];
        $scanCalls   = [];

        /** @var MockObject&RedisServiceInterface $redisService */
        $redisService = $this->createMockPublic(RedisServiceInterface::class);
        $redisService->method('rawCommand')->willReturnCallback(
            function (Connection $redis, string $command, ...$params) use (&$deletedKeys, &$scanCalls, $keysOnEachConnection): mixed {
                return match ($command) {
                    // [cursor, keys] - cursor 0 ends the SCAN loop after one iteration.
                    'SCAN' => (static function () use ($redis, $params, &$scanCalls, $keysOnEachConnection): array {
                        $scanCalls[] = ['connection' => $redis->getName(), 'args' => $params];

                        return ['0', $keysOnEachConnection];
                    })(),
                    // Report every key as idle well past any threshold - including the presence sweep's own
                    // hardcoded 86400s - so the idle check never shields a match.
                    'OBJECT' => self::PRESENCE_IDLE_TIME_SECONDS,
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

        return ['deletedKeys' => $deletedKeys, 'scanCalls' => $scanCalls];
    }

    /**
     * @param  array<int, string> $keysOnEachConnection
     * @return array<int, string>
     */
    private function runClearIdleKeysAndCaptureDeletes(array $keysOnEachConnection): array
    {
        return $this->runClearIdleKeysAndCaptureCalls($keysOnEachConnection)['deletedKeys'];
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

    #[Test]
    public function clearIdleKeys_givenPresenceKeyIdleForMoreThan24Hours_deletesIt(): void
    {
        // Arrange - a route-edit presence key with a 7-character public key.
        $presenceKey = sprintf('%spresence-%s-route-edit.E2mXPo3', $this->prefix(), config('app.type'));

        // Act
        $deletedKeys = $this->runClearIdleKeysAndCaptureDeletes([$presenceKey]);

        // Assert
        $this->assertContains($presenceKey, $deletedKeys, 'A presence key idle past 24 hours should be cleaned up by clearIdleKeys');
    }

    #[Test]
    public function clearIdleKeys_givenLiveSessionPresenceKey_deletesIt(): void
    {
        // Arrange
        $presenceKey = sprintf('%spresence-%s-live-session.E2mXPo3', $this->prefix(), config('app.type'));

        // Act
        $deletedKeys = $this->runClearIdleKeysAndCaptureDeletes([$presenceKey]);

        // Assert
        $this->assertContains($presenceKey, $deletedKeys, 'A live-session presence key idle past 24 hours should be cleaned up by clearIdleKeys');
    }

    #[Test]
    public function clearIdleKeys_sweepingPresenceKeys_scansOnlyTheDefaultConnectionOnce(): void
    {
        // Arrange
        $presenceKey = sprintf('%spresence-%s-route-edit.E2mXPo3', $this->prefix(), config('app.type'));

        // Act
        $scanCalls = $this->runClearIdleKeysAndCaptureCalls([$presenceKey])['scanCalls'];

        // Assert - the model-cache half still scans default/model_cache/cache (one SCAN call each, cursor 0
        // ends the loop), and the presence half must add exactly one more SCAN call, on 'default' only.
        $presenceScanCalls = array_values(array_filter(
            $scanCalls,
            static fn(array $call): bool => in_array('MATCH', $call['args'], true),
        ));

        $this->assertCount(1, $presenceScanCalls, 'The presence-key sweep must scan in a single pass');
        $this->assertSame('default', $presenceScanCalls[0]['connection'], 'The presence-key sweep must only scan the default connection');
    }

    #[Test]
    public function clearIdleKeys_sweepingPresenceKeys_usesAServerSideScanMatch(): void
    {
        // Arrange
        $presenceKey   = sprintf('%spresence-%s-route-edit.E2mXPo3', $this->prefix(), config('app.type'));
        $expectedMatch = sprintf('%spresence-%s-*', $this->prefix(), config('app.type'));

        // Act
        $scanCalls = $this->runClearIdleKeysAndCaptureCalls([$presenceKey])['scanCalls'];

        $presenceScanCalls = array_values(array_filter(
            $scanCalls,
            static fn(array $call): bool => in_array('MATCH', $call['args'], true),
        ));

        // Assert - args are [cursor, MATCH, pattern, COUNT, count]
        $this->assertCount(1, $presenceScanCalls);
        $this->assertSame(['0', 'MATCH', $expectedMatch, 'COUNT', '1000'], $presenceScanCalls[0]['args']);
    }
}
