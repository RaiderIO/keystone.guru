<?php

namespace Tests\Feature\App\Models\DungeonRoute;

use App\Models\DungeonRoute\DungeonRoute;
use App\Service\Cache\CacheServiceInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCases\PublicTestCase;

/**
 * Guards that dropping a route's card caches is a single bounded operation: it delegates to
 * CacheServiceInterface::dropHashCache with the per-route hash key rather than looping over every
 * orientation/locale/flag permutation.
 */
#[Group('DungeonRoute')]
#[Group('DungeonRouteDropCaches')]
final class DungeonRouteDropCachesTest extends PublicTestCase
{
    #[Test]
    public function dropCaches_givenRouteId_dropsTheRouteCardHashOnce(): void
    {
        // Arrange
        /** @var MockObject&CacheServiceInterface $cacheService */
        $cacheService = $this->createMockPublic(CacheServiceInterface::class);
        $cacheService->expects($this->once())
            ->method('dropHashCache')
            ->with('dungeonroute_card:123');
        app()->instance(CacheServiceInterface::class, $cacheService);

        // Act
        DungeonRoute::dropCaches(123);

        // Assert - handled by the mock expectation above.
    }

    #[Test]
    public function getCardCacheKey_givenRouteId_returnsPerRouteHashKey(): void
    {
        // Arrange & Act
        $key = DungeonRoute::getCardCacheKey(123);

        // Assert - the hash key carries only the route id; variants are hash fields, not separate keys.
        $this->assertSame('dungeonroute_card:123', $key);
    }

    #[Test]
    public function getCardCacheField_givenVariant_returnsFieldFromOrientationLocaleAndFlags(): void
    {
        // Arrange & Act
        $field = DungeonRoute::getCardCacheField('vertical', 'en_US', 0, 1, 0);

        // Assert - useFrontPageThumbnail defaults to 0 when omitted
        $this->assertSame('vertical:en_US_0_1_0_0', $field);
    }

    #[Test]
    public function getCardCacheField_givenUseFrontPageThumbnail_includesItInField(): void
    {
        // Arrange & Act
        $field = DungeonRoute::getCardCacheField('vertical', 'en_US', 0, 1, 0, 1);

        // Assert - a separate cache field from the non-front-page variant, so switching pages never
        // shows the other page's cached line thickness
        $this->assertSame('vertical:en_US_0_1_0_1', $field);
    }
}
