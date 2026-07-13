<?php

namespace Tests\Feature\App\Service\DungeonRoute;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonRoute\DungeonRouteAffixGroup;
use App\Models\Season;
use App\Service\Season\SeasonServiceInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;

#[Group('DungeonRouteSaveService')]
final class DungeonRouteSaveServiceSaveTemporaryTest extends DungeonRouteSaveServiceTestCase
{
    /**
     * @return MockObject&SeasonServiceInterface
     */
    private function noSeasonService(): MockObject
    {
        $seasonService = $this->createMockPublic(SeasonServiceInterface::class);
        $seasonService->method('getCurrentSeason')->willReturn(null);
        $seasonService->method('getMostRecentSeasonForDungeon')->willReturn(null);

        return $seasonService;
    }

    #[Test]
    public function saveTemporary_givenValidDungeonId_returnsTrueAndSetsExpiresAt(): void
    {
        // Arrange
        $dungeon   = $this->getRetailDungeon();
        $service   = $this->buildService(seasonService: $this->noSeasonService());
        $route     = new DungeonRoute();
        $validated = ['dungeon_id' => $dungeon->id];

        try {
            // Act
            $result = $service->saveTemporary($route, $validated);

            // Assert
            $this->assertTrue($result);
            $this->assertNotNull($route->expires_at);
            $this->assertTrue(
                $route->expires_at->isFuture(),
                sprintf('Expected expires_at to be in the future, got: %s', $route->expires_at),
            );
            $this->assertNotEmpty($route->public_key);
        } finally {
            if ($route->exists) {
                $this->cleanupRoute($route);
            }
        }
    }

    #[Test]
    public function saveTemporary_givenValidDungeonId_setsHardcodedFields(): void
    {
        // Arrange
        $dungeon   = $this->getRetailDungeon();
        $service   = $this->buildService(seasonService: $this->noSeasonService());
        $route     = new DungeonRoute();
        $validated = ['dungeon_id' => $dungeon->id];

        try {
            // Act
            $service->saveTemporary($route, $validated);

            // Assert
            $this->assertEquals(1, $route->faction_id, 'Temporary routes must have faction_id = 1');
            $this->assertFalse((bool)$route->teeming, 'Temporary routes must have teeming = false');
            $this->assertEquals(0, $route->seasonal_index, 'Temporary routes must have seasonal_index = 0');
            $this->assertEmpty($route->pull_gradient, 'Temporary routes must have empty pull_gradient');
        } finally {
            if ($route->exists) {
                $this->cleanupRoute($route);
            }
        }
    }

    #[Test]
    public function saveTemporary_givenActiveSeason_ensuresAffixGroup(): void
    {
        // Arrange
        $dungeon = $this->getRetailDungeon();
        $season  = Season::with('affixGroups.affixes')->find(Season::SEASON_TWW_S3);
        $this->assertNotNull($season, 'Season TWW S3 must exist in the database');

        $seasonService = $this->createMockPublic(SeasonServiceInterface::class);
        $seasonService->method('getCurrentSeason')->willReturn($season);

        $service   = $this->buildService(seasonService: $seasonService);
        $route     = new DungeonRoute();
        $validated = ['dungeon_id' => $dungeon->id];

        try {
            // Act
            $result = $service->saveTemporary($route, $validated);

            // Assert
            $this->assertTrue($result);
            $this->assertEquals($season->id, $route->season_id, 'Temporary route must adopt the active season');
            $this->assertGreaterThanOrEqual(
                1,
                DungeonRouteAffixGroup::where('dungeon_route_id', $route->id)->count(),
                'An active season must result in a default affix group being ensured',
            );
        } finally {
            if ($route->exists) {
                $this->cleanupRoute($route);
            }
        }
    }
}
