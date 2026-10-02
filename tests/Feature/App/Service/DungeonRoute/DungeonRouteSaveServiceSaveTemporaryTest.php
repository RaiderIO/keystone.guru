<?php

namespace Tests\Feature\App\Service\DungeonRoute;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonRoute\DungeonRouteAffixGroup;
use App\Models\Expansion;
use App\Models\GameVersion\GameVersion;
use App\Models\Mapping\MappingVersion;
use App\Models\Season;
use App\Models\User;
use App\Service\Season\SeasonServiceInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\Fixtures\Traits\CreatesDungeon;

#[Group('DungeonRouteSaveService')]
final class DungeonRouteSaveServiceSaveTemporaryTest extends DungeonRouteSaveServiceTestCase
{
    use CreatesDungeon;

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

    #[Test]
    public function saveTemporary_givenUserOnChildGameVersionAndDungeonMappedForParent_pinsParentMappingVersion(): void
    {
        // Arrange - the dungeon also has a retail (default game version) mapping version to fall back to
        $dungeon                  = $this->createDungeon(['expansion_id' => Expansion::ALL[Expansion::EXPANSION_CLASSIC]]);
        $classicEraMappingVersion = MappingVersion::create([
            'game_version_id'                 => GameVersion::ALL[GameVersion::GAME_VERSION_CLASSIC_ERA],
            'dungeon_id'                      => $dungeon->id,
            'version'                         => 1,
            'enemy_forces_required'           => 100,
            'enemy_forces_required_teeming'   => null,
            'enemy_forces_shrouded'           => 0,
            'enemy_forces_shrouded_zul_gamux' => 0,
            'timer_max_seconds'               => 1800,
            'facade_enabled'                  => false,
        ]);
        $user    = User::factory()->create(['game_version_id' => GameVersion::ALL[GameVersion::GAME_VERSION_SOD]]);
        $service = $this->buildService(seasonService: $this->noSeasonService());
        $route   = new DungeonRoute();

        try {
            $this->actingAs($user);

            // Act
            $service->saveTemporary($route, ['dungeon_id' => $dungeon->id]);

            // Assert
            $this->assertSame($classicEraMappingVersion->id, $route->mapping_version_id);
        } finally {
            if ($route->exists) {
                $this->cleanupRoute($route);
            }
            $user->delete();
        }
    }
}
