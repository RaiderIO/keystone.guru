<?php

namespace Tests\Unit\App\Service\ReadOnlyMode;

use App\Service\Cache\CacheServiceInterface;
use App\Service\ReadOnlyMode\ReadOnlyModeService;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCases\PublicTestCase;

/**
 * isReadOnly() runs on every rendered page (AppLayoutComposer, bound to layouts.app/sitepage/map)
 * and every request behind the ReadOnlyMode middleware, so it must not have its own uncaught-Redis
 * failure mode independent of CacheService::get() being made resilient in #3914.
 */
#[Group('ReadOnlyMode')]
final class ReadOnlyModeServiceTest extends PublicTestCase
{
    #[Test]
    public function isReadOnly_givenCacheServiceNeverCallsHas_reliesOnlyOnGet(): void
    {
        // Arrange
        /** @var MockObject&CacheServiceInterface $cacheService */
        $cacheService = $this->createMockPublic(CacheServiceInterface::class);
        $cacheService->expects($this->never())->method('has');
        $cacheService->method('get')->with('read_only_mode')->willReturn(true);

        $service = new ReadOnlyModeService($cacheService);

        // Act
        $result = $service->isReadOnly();

        // Assert
        $this->assertTrue($result);
    }

    #[Test]
    public function isReadOnly_givenCacheServiceReturnsNull_returnsFalse(): void
    {
        // Arrange - a degraded read (Redis blip, #3914) and a genuinely unset key both surface as null
        /** @var MockObject&CacheServiceInterface $cacheService */
        $cacheService = $this->createMockPublic(CacheServiceInterface::class);
        $cacheService->method('get')->with('read_only_mode')->willReturn(null);

        $service = new ReadOnlyModeService($cacheService);

        // Act
        $result = $service->isReadOnly();

        // Assert
        $this->assertFalse($result);
    }
}
