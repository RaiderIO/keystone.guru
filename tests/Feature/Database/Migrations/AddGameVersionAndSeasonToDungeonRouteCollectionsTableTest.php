<?php

namespace Tests\Feature\Database\Migrations;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonRoute\DungeonRouteCollection;
use App\Models\DungeonRoute\DungeonRouteCollectionRoute;
use App\Models\GameVersion\GameVersion;
use App\Models\Mapping\MappingVersion;
use App\Models\User;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

/**
 * Guards the backfill that gives every existing collection a game version and leaves it free-form.
 */
#[Group('Controller')]
final class AddGameVersionAndSeasonToDungeonRouteCollectionsTableTest extends PublicTestCase
{
    private const string MIGRATION = 'migrations/2026_09_19_120000_add_game_version_and_season_to_dungeon_route_collections_table.php';

    #[Test]
    public function backfill_givenRoutesOfSeveralGameVersions_takesTheGameVersionMostRoutesHave(): void
    {
        // Arrange
        $owner                  = User::factory()->create(['game_version_id' => GameVersion::ALL[GameVersion::GAME_VERSION_RETAIL]]);
        $otherMappingVersion    = $this->nonRetailMappingVersion();
        $dungeonRoutes          = collect();
        $dungeonRouteCollection = null;

        try {
            $dungeonRoutes->push(
                $this->createRoute($owner, $otherMappingVersion),
                $this->createRoute($owner, $otherMappingVersion),
                $this->createRoute($owner, $this->retailMappingVersion()),
            );
            $dungeonRouteCollection = $this->createLegacyCollection($owner, $dungeonRoutes->all());

            // Act
            $this->backfill();

            // Assert
            $dungeonRouteCollection->refresh();
            $this->assertSame($otherMappingVersion->game_version_id, $dungeonRouteCollection->game_version_id);
            $this->assertNull($dungeonRouteCollection->season_id, 'Every existing collection becomes free-form');
        } finally {
            $dungeonRouteCollection?->delete();
            $dungeonRoutes->each(static fn(DungeonRoute $dungeonRoute) => $dungeonRoute->delete());
            $owner->delete();
        }
    }

    #[Test]
    public function backfill_givenATie_prefersRetail(): void
    {
        // Arrange
        $owner                  = User::factory()->create(['game_version_id' => 0]);
        $dungeonRoutes          = collect();
        $dungeonRouteCollection = null;

        try {
            $dungeonRoutes->push(
                $this->createRoute($owner, $this->nonRetailMappingVersion()),
                $this->createRoute($owner, $this->retailMappingVersion()),
            );
            $dungeonRouteCollection = $this->createLegacyCollection($owner, $dungeonRoutes->all());

            // Act
            $this->backfill();

            // Assert
            $this->assertSame(GameVersion::ALL[GameVersion::GAME_VERSION_RETAIL], $dungeonRouteCollection->refresh()->game_version_id);
        } finally {
            $dungeonRouteCollection?->delete();
            $dungeonRoutes->each(static fn(DungeonRoute $dungeonRoute) => $dungeonRoute->delete());
            $owner->delete();
        }
    }

    #[Test]
    public function backfill_givenAnEmptyCollection_takesTheOwnersCurrentGameVersion(): void
    {
        // Arrange
        $gameVersionId          = GameVersion::ALL[GameVersion::GAME_VERSION_CATA];
        $owner                  = User::factory()->create(['game_version_id' => $gameVersionId]);
        $dungeonRouteCollection = null;

        try {
            $dungeonRouteCollection = $this->createLegacyCollection($owner, []);

            // Act
            $this->backfill();

            // Assert
            $this->assertSame($gameVersionId, $dungeonRouteCollection->refresh()->game_version_id);
        } finally {
            $dungeonRouteCollection?->delete();
            $owner->delete();
        }
    }

    #[Test]
    public function backfill_givenAnEmptyCollectionOfAnOwnerWithoutAGameVersion_takesRetail(): void
    {
        // Arrange
        $owner                  = User::factory()->create(['game_version_id' => 0]);
        $dungeonRouteCollection = null;

        try {
            $dungeonRouteCollection = $this->createLegacyCollection($owner, []);

            // Act
            $this->backfill();

            // Assert
            $this->assertSame(GameVersion::ALL[GameVersion::GAME_VERSION_RETAIL], $dungeonRouteCollection->refresh()->game_version_id);
        } finally {
            $dungeonRouteCollection?->delete();
            $owner->delete();
        }
    }

    #[Test]
    public function backfill_givenACollectionWithAGameVersion_leavesItAlone(): void
    {
        // Arrange
        $owner                  = User::factory()->create(['game_version_id' => GameVersion::ALL[GameVersion::GAME_VERSION_RETAIL]]);
        $gameVersionId          = GameVersion::ALL[GameVersion::GAME_VERSION_MOP];
        $dungeonRouteCollection = null;

        try {
            $dungeonRouteCollection = DungeonRouteCollection::factory()->create([
                'user_id'         => $owner->id,
                'game_version_id' => $gameVersionId,
            ]);

            // Act
            $this->backfill();

            // Assert
            $this->assertSame($gameVersionId, $dungeonRouteCollection->refresh()->game_version_id);
        } finally {
            $dungeonRouteCollection?->delete();
            $owner->delete();
        }
    }

    private function backfill(): void
    {
        $migration = require database_path(self::MIGRATION);
        $migration->backfill();
    }

    /**
     * A collection as it was before the migration: no game version and no season.
     *
     * @param array<int, DungeonRoute> $dungeonRoutes
     */
    private function createLegacyCollection(User $owner, array $dungeonRoutes): DungeonRouteCollection
    {
        $dungeonRouteCollection = DungeonRouteCollection::factory()->create(['user_id' => $owner->id]);
        DungeonRouteCollection::query()->whereKey($dungeonRouteCollection->id)->update(['game_version_id' => null, 'season_id' => null]);

        foreach ($dungeonRoutes as $order => $dungeonRoute) {
            DungeonRouteCollectionRoute::create([
                'dungeon_route_collection_id' => $dungeonRouteCollection->id,
                'dungeon_route_id'            => $dungeonRoute->id,
                'order'                       => $order,
            ]);
        }

        return $dungeonRouteCollection;
    }

    private function createRoute(User $owner, MappingVersion $mappingVersion): DungeonRoute
    {
        return DungeonRoute::factory()->create([
            'author_id'          => $owner->id,
            'dungeon_id'         => $mappingVersion->dungeon_id,
            'mapping_version_id' => $mappingVersion->id,
            'season_id'          => null,
            'expires_at'         => null,
        ]);
    }

    private function retailMappingVersion(): MappingVersion
    {
        return MappingVersion::query()
            ->where('game_version_id', GameVersion::ALL[GameVersion::GAME_VERSION_RETAIL])
            ->orderByDesc('id')
            ->firstOrFail();
    }

    private function nonRetailMappingVersion(): MappingVersion
    {
        return MappingVersion::query()
            ->where('game_version_id', '!=', GameVersion::ALL[GameVersion::GAME_VERSION_RETAIL])
            ->orderByDesc('id')
            ->firstOrFail();
    }
}
