<?php

namespace Tests\Feature\App\Service\View;

use App\Models\Expansion;
use App\Models\GameServerRegion;
use App\Service\Expansion\ExpansionServiceInterface;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\ServiceFixtures;
use Tests\TestCases\PublicTestCase;

#[Group('ViewService')]
#[Group('ViewServiceCachedGlobal')]
final class ViewServiceCachedGlobalTest extends PublicTestCase
{
    /**
     * No next season is revealed for most of the year, so a null that never caches would be recomputed
     * on every page view.
     */
    #[Test]
    public function getNextSeasonForRegion_givenNoNextSeason_computesItOnceAcrossRequests(): void
    {
        // Arrange
        Cache::store('tmp_file')->flush();

        $region           = GameServerRegion::getUserOrDefaultRegion();
        $expansionService = $this->createMockPublic(ExpansionServiceInterface::class);
        $expansionService->method('getCurrentExpansion')
            ->willReturn(Expansion::findOrFail(Expansion::ALL[Expansion::EXPANSION_SHADOWLANDS]));
        $expansionService->method('getNextExpansion')->willReturn(null);
        $expansionService->expects($this->once())->method('getNextSeason')->willReturn(null);

        try {
            // Act
            $firstRequest = ServiceFixtures::getViewServiceMock($this, expansionService: $expansionService)
                ->getNextSeasonForRegion($region);
            $secondRequest = ServiceFixtures::getViewServiceMock($this, expansionService: $expansionService)
                ->getNextSeasonForRegion($region);

            // Assert
            $this->assertNull($firstRequest);
            $this->assertNull($secondRequest);
        } finally {
            Cache::store('tmp_file')->flush();
        }
    }

    #[Test]
    public function getCurrentExpansionForRegion_givenACachedExpansion_returnsItInALaterRequest(): void
    {
        // Arrange
        Cache::store('tmp_file')->flush();

        $region           = GameServerRegion::getUserOrDefaultRegion();
        $expansion        = Expansion::findOrFail(Expansion::ALL[Expansion::EXPANSION_SHADOWLANDS]);
        $expansionService = $this->createMockPublic(ExpansionServiceInterface::class);
        $expansionService->expects($this->once())->method('getCurrentExpansion')->willReturn($expansion);

        try {
            // Act
            ServiceFixtures::getViewServiceMock($this, expansionService: $expansionService)
                ->getCurrentExpansionForRegion($region);
            $result = ServiceFixtures::getViewServiceMock($this, expansionService: $expansionService)
                ->getCurrentExpansionForRegion($region);

            // Assert
            $this->assertSame($expansion->id, $result->id);
        } finally {
            Cache::store('tmp_file')->flush();
        }
    }
}
