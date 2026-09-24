<?php

namespace Tests\Unit\App\Service\DungeonRoute;

use App\Service\Cache\CacheServiceInterface;
use App\Service\DungeonRoute\ThumbnailGenerationToggleService;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCases\PublicTestCase;

#[Group('ThumbnailGenerationToggle')]
final class ThumbnailGenerationToggleServiceTest extends PublicTestCase
{
    #[Test]
    public function isPaused_givenNothingStored_returnsFalse(): void
    {
        // Arrange - a degraded read (Redis blip) and a genuinely unset key both surface as null, and
        // neither may pause generation: the safe default is to keep rendering thumbnails.
        /** @var MockObject&CacheServiceInterface $cacheService */
        $cacheService = $this->createMockPublic(CacheServiceInterface::class);
        $cacheService->method('get')->with('thumbnail_generation_paused')->willReturn(null);

        $service = new ThumbnailGenerationToggleService($cacheService);

        // Act
        $result = $service->isPaused();

        // Assert
        $this->assertFalse($result);
    }

    #[Test]
    public function isPaused_givenPausedStored_returnsTrue(): void
    {
        // Arrange
        /** @var MockObject&CacheServiceInterface $cacheService */
        $cacheService = $this->createMockPublic(CacheServiceInterface::class);
        $cacheService->expects($this->never())->method('has');
        $cacheService->method('get')->with('thumbnail_generation_paused')->willReturn(true);

        $service = new ThumbnailGenerationToggleService($cacheService);

        // Act
        $result = $service->isPaused();

        // Assert
        $this->assertTrue($result);
    }

    #[Test]
    public function setPaused_givenTrue_writesTheFlagToTheCache(): void
    {
        // Arrange
        /** @var MockObject&CacheServiceInterface $cacheService */
        $cacheService = $this->createMockPublic(CacheServiceInterface::class);
        $cacheService->expects($this->once())
            ->method('set')
            ->with('thumbnail_generation_paused', true)
            ->willReturn(true);

        $service = new ThumbnailGenerationToggleService($cacheService);

        // Act
        $result = $service->setPaused(true);

        // Assert
        $this->assertTrue($result);
    }
}
