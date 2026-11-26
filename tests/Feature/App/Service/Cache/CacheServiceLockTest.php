<?php

namespace Tests\Feature\App\Service\Cache;

use App\Service\Cache\CacheServiceInterface;
use Illuminate\Cache\ArrayStore;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Cache')]
#[Group('CacheServiceLock')]
final class CacheServiceLockTest extends PublicTestCase
{
    private const string LOCK_KEY = 'cache-service-lock-test';

    #[Test]
    public function lock_givenAHolderWhoseLockLapsed_doesNotLetItReleaseTheNextHoldersLock(): void
    {
        // Arrange
        $cacheService = app(CacheServiceInterface::class);
        /** @var ArrayStore $store */
        $store = Cache::getStore();
        $this->assertInstanceOf(ArrayStore::class, $store, 'The test reads the lock owner from the array cache store');

        try {
            $lapsedHolderOwner = $cacheService->lock(self::LOCK_KEY, static fn(): string => $store->locks[self::LOCK_KEY]['owner'], 0);

            // Act
            [$isReleasedByLapsedHolder, $isStillHeld] = $cacheService->lock(self::LOCK_KEY, static fn(): array => [
                Cache::restoreLock(self::LOCK_KEY, $lapsedHolderOwner)->release(),
                !Cache::lock(self::LOCK_KEY, 10)->get(),
            ], 0);

            // Assert
            $this->assertFalse($isReleasedByLapsedHolder);
            $this->assertTrue($isStillHeld, 'The next holder must keep its lock');
        } finally {
            Cache::lock(self::LOCK_KEY)->forceRelease();
        }
    }
}
