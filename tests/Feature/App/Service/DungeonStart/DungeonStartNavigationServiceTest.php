<?php

namespace Tests\Feature\App\Service\DungeonStart;

use App\Models\Dungeon;
use App\Models\DungeonStart;
use App\Models\Floor\Floor;
use App\Models\GameVersion\GameVersion;
use App\Models\Mapping\MappingVersion;
use App\Service\DungeonStart\DungeonStartNavigationServiceInterface;
use Database\Factories\FloorFactory;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\Traits\CreatesDungeon;
use Tests\TestCases\PublicTestCase;

#[Group('DungeonStart')]
#[Group('DungeonStartNavigationService')]
final class DungeonStartNavigationServiceTest extends PublicTestCase
{
    use CreatesDungeon;

    #[Test]
    public function resolveNavigation_givenTargetMappedInViewedGameVersion_returnsTargetInViewedGameVersion(): void
    {
        // Arrange
        $continent = $this->createDungeonInGameVersion(GameVersion::GAME_VERSION_CLASSIC_ERA);
        $target    = $this->createDungeonInGameVersion(GameVersion::GAME_VERSION_CLASSIC_ERA);
        // Retail comes first in the selectors, so only the viewed game version can pick classic
        $this->createMappingVersion($target, GameVersion::GAME_VERSION_RETAIL);
        $dungeonStart = $this->createDungeonStart($continent, $target);

        // Act
        $navigation = $this->getService()->resolveNavigation($dungeonStart, $this->getGameVersion(GameVersion::GAME_VERSION_CLASSIC_ERA));

        // Assert
        $this->assertNotNull($navigation);
        $this->assertFalse($navigation->isBackLink);
        $this->assertSame($target->id, $navigation->dungeon->id);
        $this->assertSame(GameVersion::GAME_VERSION_CLASSIC_ERA, $navigation->gameVersion->key);
        $this->assertNull($navigation->floor);
        $this->assertSame(route('dungeon.explore.gameversion.view', [
            'gameVersion' => GameVersion::GAME_VERSION_CLASSIC_ERA,
            'dungeon'     => $target,
        ]), $navigation->getUrl());
    }

    #[Test]
    public function resolveNavigation_givenTargetOnlyMappedInOtherGameVersions_returnsTargetInFirstOfThoseGameVersions(): void
    {
        // Arrange
        $continent = $this->createDungeonInGameVersion(GameVersion::GAME_VERSION_CLASSIC_ERA);
        $target    = $this->createDungeonInGameVersion(GameVersion::GAME_VERSION_MOP);
        $this->createMappingVersion($target, GameVersion::GAME_VERSION_RETAIL);
        $dungeonStart = $this->createDungeonStart($continent, $target);

        // Act
        $navigation = $this->getService()->resolveNavigation($dungeonStart, $this->getGameVersion(GameVersion::GAME_VERSION_CLASSIC_ERA));

        // Assert
        $this->assertNotNull($navigation);
        $this->assertSame($target->id, $navigation->dungeon->id);
        $this->assertSame(GameVersion::GAME_VERSION_RETAIL, $navigation->gameVersion->key);
    }

    #[Test]
    public function resolveNavigation_givenInactiveTarget_returnsNull(): void
    {
        // Arrange
        $continent    = $this->createDungeonInGameVersion(GameVersion::GAME_VERSION_CLASSIC_ERA);
        $target       = $this->createDungeonInGameVersion(GameVersion::GAME_VERSION_CLASSIC_ERA, false);
        $dungeonStart = $this->createDungeonStart($continent, $target);

        // Act
        $navigation = $this->getService()->resolveNavigation($dungeonStart, $this->getGameVersion(GameVersion::GAME_VERSION_CLASSIC_ERA));

        // Assert
        $this->assertNull($navigation);
    }

    #[Test]
    public function resolveNavigation_givenTargetWithoutMapping_returnsNull(): void
    {
        // Arrange
        $continent    = $this->createDungeonInGameVersion(GameVersion::GAME_VERSION_CLASSIC_ERA);
        $target       = $this->createDungeon(['active' => true], withMappingVersion: false);
        $dungeonStart = $this->createDungeonStart($continent, $target);

        // Act
        $navigation = $this->getService()->resolveNavigation($dungeonStart, $this->getGameVersion(GameVersion::GAME_VERSION_CLASSIC_ERA));

        // Assert
        $this->assertNull($navigation);
    }

    #[Test]
    public function resolveNavigation_givenStartWithoutTargetThatIsTargeted_returnsBackLinkToTheTargetingStartsFloor(): void
    {
        // Arrange
        $continent      = $this->createDungeonInGameVersion(GameVersion::GAME_VERSION_FOREVER);
        $continentFloor = FloorFactory::new()->create([
            'dungeon_id' => $continent->id,
            'index'      => 2,
            'name'       => 'Test Zone',
            'default'    => false,
        ]);
        $dungeon = $this->createDungeonInGameVersion(GameVersion::GAME_VERSION_RETAIL);
        $this->createDungeonStart($continent, $dungeon, $continentFloor);
        $dungeonStart = $this->createDungeonStart($dungeon, null);

        // Act
        $navigation = $this->getService()->resolveNavigation($dungeonStart, $this->getGameVersion(GameVersion::GAME_VERSION_RETAIL));

        // Assert
        $this->assertNotNull($navigation);
        $this->assertTrue($navigation->isBackLink);
        $this->assertSame($continent->id, $navigation->dungeon->id);
        $this->assertSame($continentFloor->id, $navigation->floor?->id);
        $this->assertSame(GameVersion::GAME_VERSION_FOREVER, $navigation->gameVersion->key);
        $this->assertSame(route('dungeon.explore.gameversion.view.floor', [
            'gameVersion' => GameVersion::GAME_VERSION_FOREVER,
            'dungeon'     => $continent,
            'floorIndex'  => 2,
        ]), $navigation->getUrl());
    }

    #[Test]
    public function resolveNavigation_givenStartWithoutTargetThatIsNotTargeted_returnsNull(): void
    {
        // Arrange
        $dungeon      = $this->createDungeonInGameVersion(GameVersion::GAME_VERSION_RETAIL);
        $dungeonStart = $this->createDungeonStart($dungeon, null);

        // Act
        $navigation = $this->getService()->resolveNavigation($dungeonStart, $this->getGameVersion(GameVersion::GAME_VERSION_RETAIL));

        // Assert
        $this->assertNull($navigation);
    }

    #[Test]
    public function resolveNavigation_givenOnlyTargetedByAStartInItsOwnDungeon_returnsNull(): void
    {
        // Arrange
        $dungeon = $this->createDungeonInGameVersion(GameVersion::GAME_VERSION_RETAIL);
        $this->createDungeonStart($dungeon, $dungeon);
        $dungeonStart = $this->createDungeonStart($dungeon, null);

        // Act
        $navigation = $this->getService()->resolveNavigation($dungeonStart, $this->getGameVersion(GameVersion::GAME_VERSION_RETAIL));

        // Assert
        $this->assertNull($navigation);
    }

    #[Test]
    public function resolveNavigation_givenOnlyTargetedFromAnOutdatedMappingVersion_returnsNull(): void
    {
        // Arrange
        $continent              = $this->createDungeonInGameVersion(GameVersion::GAME_VERSION_FOREVER);
        $outdatedMappingVersion = $continent->mappingVersions()->firstOrFail();
        $this->createMappingVersion($continent, GameVersion::GAME_VERSION_FOREVER, 2);
        $dungeon = $this->createDungeonInGameVersion(GameVersion::GAME_VERSION_RETAIL);
        $this->createDungeonStart($continent, $dungeon, null, $outdatedMappingVersion);
        $dungeonStart = $this->createDungeonStart($dungeon, null);

        // Act
        $navigation = $this->getService()->resolveNavigation($dungeonStart, $this->getGameVersion(GameVersion::GAME_VERSION_RETAIL));

        // Assert
        $this->assertNull($navigation);
    }

    #[Test]
    public function getNavigationsForMappingVersion_givenStarts_returnsOnlyTheNavigableOnesKeyedById(): void
    {
        // Arrange
        $continent           = $this->createDungeonInGameVersion(GameVersion::GAME_VERSION_CLASSIC_ERA);
        $target              = $this->createDungeonInGameVersion(GameVersion::GAME_VERSION_CLASSIC_ERA);
        $inactiveTarget      = $this->createDungeonInGameVersion(GameVersion::GAME_VERSION_CLASSIC_ERA, false);
        $navigableStart      = $this->createDungeonStart($continent, $target);
        $inactiveTargetStart = $this->createDungeonStart($continent, $inactiveTarget);
        $untargetedStart     = $this->createDungeonStart($continent, null);

        // Act
        $navigations = $this->getService()->getNavigationsForMappingVersion(
            $continent->mappingVersions()->firstOrFail(),
            $this->getGameVersion(GameVersion::GAME_VERSION_CLASSIC_ERA),
        );

        // Assert
        $this->assertSame([$navigableStart->id], $navigations->keys()->all());
        $this->assertSame($target->id, $navigations->get($navigableStart->id)->dungeon->id);
        $this->assertFalse($navigations->has($inactiveTargetStart->id));
        $this->assertFalse($navigations->has($untargetedStart->id));
    }

    #[Test]
    public function getNavigationsForMappingVersion_givenStartWithoutTargetThatIsTargeted_returnsItsBackLink(): void
    {
        // Arrange
        $continent = $this->createDungeonInGameVersion(GameVersion::GAME_VERSION_FOREVER);
        $dungeon   = $this->createDungeonInGameVersion(GameVersion::GAME_VERSION_CLASSIC_ERA);
        $this->createDungeonStart($continent, $dungeon);
        $dungeonStart = $this->createDungeonStart($dungeon, null);

        // Act
        $navigations = $this->getService()->getNavigationsForMappingVersion(
            $dungeon->mappingVersions()->firstOrFail(),
            $this->getGameVersion(GameVersion::GAME_VERSION_CLASSIC_ERA),
        );

        // Assert
        $this->assertSame([$dungeonStart->id], $navigations->keys()->all());
        $this->assertTrue($navigations->get($dungeonStart->id)->isBackLink);
        $this->assertSame($continent->id, $navigations->get($dungeonStart->id)->dungeon->id);
    }

    #[Test]
    public function getNavigationsForMappingVersion_givenMoreTargets_runsNoMoreQueries(): void
    {
        // Arrange
        $gameVersion          = $this->getGameVersion(GameVersion::GAME_VERSION_CLASSIC_ERA);
        $oneTargetContinent   = $this->createDungeonInGameVersion(GameVersion::GAME_VERSION_CLASSIC_ERA);
        $fourTargetsContinent = $this->createDungeonInGameVersion(GameVersion::GAME_VERSION_CLASSIC_ERA);
        $this->createDungeonStart($oneTargetContinent, $this->createDungeonInGameVersion(GameVersion::GAME_VERSION_CLASSIC_ERA));
        for ($i = 0; $i < 4; $i++) {
            $this->createDungeonStart($fourTargetsContinent, $this->createDungeonInGameVersion(GameVersion::GAME_VERSION_CLASSIC_ERA));
        }
        $oneTargetMappingVersion   = $oneTargetContinent->mappingVersions()->firstOrFail();
        $fourTargetsMappingVersion = $fourTargetsContinent->mappingVersions()->firstOrFail();

        // Act
        $oneTargetQueryCount   = $this->countQueries(fn() => $this->getService()->getNavigationsForMappingVersion($oneTargetMappingVersion, $gameVersion));
        $fourTargetsQueryCount = $this->countQueries(fn() => $this->getService()->getNavigationsForMappingVersion($fourTargetsMappingVersion, $gameVersion));

        // Assert
        $this->assertCount(4, $this->getService()->getNavigationsForMappingVersion($fourTargetsMappingVersion, $gameVersion));
        $this->assertSame($oneTargetQueryCount, $fourTargetsQueryCount);
    }

    private function countQueries(callable $callable): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();

        try {
            $callable();

            return count(DB::getQueryLog());
        } finally {
            DB::disableQueryLog();
        }
    }

    private function getService(): DungeonStartNavigationServiceInterface
    {
        return app(DungeonStartNavigationServiceInterface::class);
    }

    private function getGameVersion(string $key): GameVersion
    {
        return GameVersion::query()->where('key', $key)->firstOrFail();
    }

    private function createDungeonInGameVersion(string $gameVersionKey, bool $active = true): Dungeon
    {
        return $this->createDungeon(['active' => $active], mappingVersionAttributes: [
            'game_version_id' => $this->getGameVersion($gameVersionKey)->id,
        ]);
    }

    private function createMappingVersion(Dungeon $dungeon, string $gameVersionKey, int $version = 1): MappingVersion
    {
        return MappingVersion::create([
            'game_version_id'                 => $this->getGameVersion($gameVersionKey)->id,
            'dungeon_id'                      => $dungeon->id,
            'version'                         => $version,
            'enemy_forces_required'           => 100,
            'enemy_forces_required_teeming'   => null,
            'enemy_forces_shrouded'           => 0,
            'enemy_forces_shrouded_zul_gamux' => 0,
            'timer_max_seconds'               => 1800,
            'facade_enabled'                  => false,
            'mdt_mapping_hash'                => null,
            'mdt_changes_pending'             => false,
        ]);
    }

    /**
     * Deleted with its mapping version when the test's dungeons are torn down.
     */
    private function createDungeonStart(
        Dungeon         $dungeon,
        ?Dungeon        $targetDungeon,
        ?Floor          $floor = null,
        ?MappingVersion $mappingVersion = null,
    ): DungeonStart {
        return DungeonStart::factory()->create([
            'mapping_version_id' => ($mappingVersion ?? $dungeon->mappingVersions()->firstOrFail())->id,
            'floor_id'           => ($floor ?? $dungeon->floors()->firstOrFail())->id,
            'target_dungeon_id'  => $targetDungeon?->id,
        ]);
    }
}
