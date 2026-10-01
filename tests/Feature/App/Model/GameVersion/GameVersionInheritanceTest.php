<?php

namespace Tests\Feature\App\Model\GameVersion;

use App\Models\Dungeon;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonRoute\DungeonRouteCollection;
use App\Models\Enemy;
use App\Models\Expansion;
use App\Models\GameVersion\GameVersion;
use App\Models\Mapping\MappingVersion;
use App\Models\Npc\NpcHealth;
use App\Repositories\Interfaces\GameVersion\GameVersionRepositoryInterface;
use App\Service\Mapping\MappingServiceInterface;
use Illuminate\Database\Eloquent\Builder;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\Traits\CreatesDungeon;
use Tests\Fixtures\Traits\CreatesNpc;
use Tests\TestCases\PublicTestCase;

#[Group('GameVersion')]
#[Group('GameVersionInheritance')]
final class GameVersionInheritanceTest extends PublicTestCase
{
    use CreatesDungeon;
    use CreatesNpc;

    #[Test]
    public function seededGameVersions_givenTbcSodAndForever_inheritFromClassicEra(): void
    {
        // Arrange
        $classicEraId = GameVersion::ALL[GameVersion::GAME_VERSION_CLASSIC_ERA];

        // Act
        $parentIdsByKey = GameVersion::query()->pluck('parent_game_version_id', 'key');

        // Assert
        $this->assertSame($classicEraId, $parentIdsByKey->get(GameVersion::GAME_VERSION_TBC));
        $this->assertSame($classicEraId, $parentIdsByKey->get(GameVersion::GAME_VERSION_SOD));
        $this->assertSame($classicEraId, $parentIdsByKey->get(GameVersion::GAME_VERSION_FOREVER));
        $this->assertNull($parentIdsByKey->get(GameVersion::GAME_VERSION_CLASSIC_ERA));
        $this->assertNull($parentIdsByKey->get(GameVersion::GAME_VERSION_RETAIL));
    }

    #[Test]
    public function seededGameVersions_givenAllConstant_matchesSeededIds(): void
    {
        // Arrange
        $expected = GameVersion::ALL;

        // Act
        $idsByKey = GameVersion::query()->pluck('id', 'key')->toArray();

        // Assert
        $this->assertEquals($expected, $idsByKey);
    }

    #[Test]
    public function getMappingVersionGameVersionIds_givenChildGameVersion_returnsOwnIdBeforeParentId(): void
    {
        // Arrange
        $sod = $this->gameVersion(GameVersion::GAME_VERSION_SOD);

        // Act
        $ids = $sod->getMappingVersionGameVersionIds();

        // Assert
        $this->assertSame([GameVersion::ALL[GameVersion::GAME_VERSION_SOD], GameVersion::ALL[GameVersion::GAME_VERSION_CLASSIC_ERA]], $ids);
    }

    #[Test]
    public function getMappingVersionGameVersionIds_givenGameVersionWithoutParent_returnsOnlyOwnId(): void
    {
        // Arrange
        $classicEra = $this->gameVersion(GameVersion::GAME_VERSION_CLASSIC_ERA);

        // Act
        $ids = $classicEra->getMappingVersionGameVersionIds();

        // Assert
        $this->assertSame([GameVersion::ALL[GameVersion::GAME_VERSION_CLASSIC_ERA]], $ids);
    }

    #[Test]
    public function forGameVersion_givenDungeonMappedForParentOnly_includesItForParentAndChildren(): void
    {
        // Arrange
        $dungeon = $this->createClassicEraDungeon();

        // Act
        $idsForClassicEra = Dungeon::query()->forGameVersion($this->gameVersion(GameVersion::GAME_VERSION_CLASSIC_ERA))->pluck('id');
        $idsForSod        = Dungeon::query()->forGameVersion($this->gameVersion(GameVersion::GAME_VERSION_SOD))->pluck('id');
        $idsForTbc        = Dungeon::query()->forGameVersion($this->gameVersion(GameVersion::GAME_VERSION_TBC))->pluck('id');
        $idsForRetail     = Dungeon::query()->forGameVersion($this->gameVersion(GameVersion::GAME_VERSION_RETAIL))->pluck('id');

        // Assert
        $this->assertContains($dungeon->id, $idsForClassicEra);
        $this->assertContains($dungeon->id, $idsForSod);
        $this->assertContains($dungeon->id, $idsForTbc);
        $this->assertNotContains($dungeon->id, $idsForRetail);
    }

    #[Test]
    public function forGameVersion_givenDungeonMappedForChildOnly_excludesItFromParentAndSiblings(): void
    {
        // Arrange
        $dungeon = $this->createDungeon(
            ['expansion_id' => Expansion::ALL[Expansion::EXPANSION_CLASSIC]],
            mappingVersionAttributes: ['game_version_id' => GameVersion::ALL[GameVersion::GAME_VERSION_SOD]],
        );

        // Act
        $idsForSod        = Dungeon::query()->forGameVersion($this->gameVersion(GameVersion::GAME_VERSION_SOD))->pluck('id');
        $idsForClassicEra = Dungeon::query()->forGameVersion($this->gameVersion(GameVersion::GAME_VERSION_CLASSIC_ERA))->pluck('id');
        $idsForTbc        = Dungeon::query()->forGameVersion($this->gameVersion(GameVersion::GAME_VERSION_TBC))->pluck('id');

        // Assert
        $this->assertContains($dungeon->id, $idsForSod);
        $this->assertNotContains($dungeon->id, $idsForClassicEra);
        $this->assertNotContains($dungeon->id, $idsForTbc);
    }

    #[Test]
    public function getCurrentMappingVersionForGameVersion_givenChildAndParentMappingVersions_returnsChildMappingVersion(): void
    {
        // Arrange
        $dungeon           = $this->createClassicEraDungeon();
        $sodMappingVersion = $this->createMappingVersion($dungeon, GameVersion::GAME_VERSION_SOD);
        $freshDungeon      = Dungeon::findOrFail($dungeon->id);

        // Act
        $forSod        = $freshDungeon->getCurrentMappingVersionForGameVersion($this->gameVersion(GameVersion::GAME_VERSION_SOD));
        $forClassicEra = $freshDungeon->getCurrentMappingVersionForGameVersion($this->gameVersion(GameVersion::GAME_VERSION_CLASSIC_ERA));

        // Assert
        $this->assertSame($sodMappingVersion->id, $forSod?->id);
        $this->assertSame(GameVersion::ALL[GameVersion::GAME_VERSION_CLASSIC_ERA], $forClassicEra?->game_version_id);
    }

    #[Test]
    public function getCurrentMappingVersionForGameVersion_givenParentMappingVersionOnly_returnsParentMappingVersion(): void
    {
        // Arrange
        $dungeon  = $this->createClassicEraDungeon();
        $expected = $dungeon->mappingVersions()->firstOrFail();

        // Act
        $mappingVersion = Dungeon::findOrFail($dungeon->id)->getCurrentMappingVersionForGameVersion($this->gameVersion(GameVersion::GAME_VERSION_TBC));

        // Assert
        $this->assertSame($expected->id, $mappingVersion?->id);
    }

    #[Test]
    public function getCurrentMappingVersionForGameVersion_givenChildMappingVersionOnly_returnsNullForParent(): void
    {
        // Arrange
        $dungeon = $this->createDungeon(
            ['expansion_id' => Expansion::ALL[Expansion::EXPANSION_CLASSIC]],
            mappingVersionAttributes: ['game_version_id' => GameVersion::ALL[GameVersion::GAME_VERSION_TBC]],
        );

        // Act
        $mappingVersion = Dungeon::findOrFail($dungeon->id)->getCurrentMappingVersionForGameVersion($this->gameVersion(GameVersion::GAME_VERSION_CLASSIC_ERA));

        // Assert
        $this->assertNull($mappingVersion);
    }

    #[Test]
    public function createNewBareMappingVersion_givenChildOfDungeonWithParentMappingVersionsOnly_startsAtVersionOne(): void
    {
        // Arrange
        $dungeon = $this->createClassicEraDungeon();
        $this->createMappingVersion($dungeon, GameVersion::GAME_VERSION_CLASSIC_ERA, 3);
        $tbc = $this->gameVersion(GameVersion::GAME_VERSION_TBC);

        // Act
        $mappingVersion = app(MappingServiceInterface::class)->createNewBareMappingVersion(Dungeon::findOrFail($dungeon->id), $tbc);

        // Assert
        $this->assertSame($tbc->id, $mappingVersion->game_version_id);
        $this->assertSame(1, $mappingVersion->version, 'Mapping versions are numbered per game version, so the parent\'s must not count');
    }

    #[Test]
    public function hasMappingVersionForGameVersion_givenParentMappingVersionOnly_returnsFalseForChild(): void
    {
        // Arrange
        $dungeon = $this->createClassicEraDungeon();

        // Act
        $hasForSod        = $dungeon->hasMappingVersionForGameVersion($this->gameVersion(GameVersion::GAME_VERSION_SOD));
        $hasForClassicEra = $dungeon->hasMappingVersionForGameVersion($this->gameVersion(GameVersion::GAME_VERSION_CLASSIC_ERA));

        // Assert
        $this->assertFalse($hasForSod, 'Combat log extraction writes healths per game version, so it must not treat the parent\'s dungeons as its own');
        $this->assertTrue($hasForClassicEra);
    }

    #[Test]
    public function canUseMappingVersion_givenParentChildAndSiblingMappingVersions_acceptsOnlyOwnAndParent(): void
    {
        // Arrange
        $sod = $this->gameVersion(GameVersion::GAME_VERSION_SOD);

        // Act
        $usesOwn     = $this->canUseMappingVersion($sod, $this->makeMappingVersion(GameVersion::GAME_VERSION_SOD));
        $usesParent  = $this->canUseMappingVersion($sod, $this->makeMappingVersion(GameVersion::GAME_VERSION_CLASSIC_ERA));
        $usesSibling = $this->canUseMappingVersion($sod, $this->makeMappingVersion(GameVersion::GAME_VERSION_TBC));
        $parentUses  = $this->canUseMappingVersion($this->gameVersion(GameVersion::GAME_VERSION_CLASSIC_ERA), $this->makeMappingVersion(GameVersion::GAME_VERSION_SOD));

        // Assert
        $this->assertTrue($usesOwn);
        $this->assertTrue($usesParent);
        $this->assertFalse($usesSibling);
        $this->assertFalse($parentUses);
    }

    #[Test]
    public function canUseMappingVersion_givenParentMappingVersionOfDungeonWithOwnMappingVersion_returnsFalse(): void
    {
        // Arrange
        $overriddenDungeon = $this->createClassicEraDungeon();
        $this->createMappingVersion($overriddenDungeon, GameVersion::GAME_VERSION_SOD);
        $inheritedDungeon               = $this->createClassicEraDungeon();
        $overriddenParentMappingVersion = $this->findMappingVersion($overriddenDungeon, GameVersion::GAME_VERSION_CLASSIC_ERA);
        $inheritedParentMappingVersion  = $this->findMappingVersion($inheritedDungeon, GameVersion::GAME_VERSION_CLASSIC_ERA);
        $sod                            = $this->gameVersion(GameVersion::GAME_VERSION_SOD);

        // Act
        $usesOverriddenParent = $this->canUseMappingVersion($sod, $overriddenParentMappingVersion);
        $usesInheritedParent  = $this->canUseMappingVersion($sod, $inheritedParentMappingVersion);
        $tbcUsesOverridden    = $this->canUseMappingVersion($this->gameVersion(GameVersion::GAME_VERSION_TBC), $overriddenParentMappingVersion);

        // Assert
        $this->assertFalse($usesOverriddenParent);
        $this->assertTrue($usesInheritedParent);
        $this->assertTrue($tbcUsesOverridden, 'Only the game version with its own mapping version stops using the parent\'s');
    }

    #[Test]
    public function listsDungeonOfMappingVersion_givenParentChildAndSiblingMappingVersions_acceptsOnlyOwnAndParent(): void
    {
        // Arrange
        $sod = $this->gameVersion(GameVersion::GAME_VERSION_SOD);

        // Act
        $listsOwn     = $sod->listsDungeonOfMappingVersion($this->makeMappingVersion(GameVersion::GAME_VERSION_SOD));
        $listsParent  = $sod->listsDungeonOfMappingVersion($this->makeMappingVersion(GameVersion::GAME_VERSION_CLASSIC_ERA));
        $listsSibling = $sod->listsDungeonOfMappingVersion($this->makeMappingVersion(GameVersion::GAME_VERSION_TBC));

        // Assert
        $this->assertTrue($listsOwn);
        $this->assertTrue($listsParent);
        $this->assertFalse($listsSibling);
    }

    #[Test]
    public function whereMappingVersionIsUsable_givenDungeonWithChildAndParentMappingVersions_returnsOnlyRoutesOnTheChildMappingVersion(): void
    {
        // Arrange
        $overriddenDungeon         = $this->createClassicEraDungeon();
        $sodMappingVersion         = $this->createMappingVersion($overriddenDungeon, GameVersion::GAME_VERSION_SOD);
        $inheritedDungeon          = $this->createClassicEraDungeon();
        $overriddenClassicEraRoute = $this->createDungeonRoute($this->findMappingVersion($overriddenDungeon, GameVersion::GAME_VERSION_CLASSIC_ERA));
        $overriddenSodRoute        = $this->createDungeonRoute($sodMappingVersion);
        $inheritedClassicEraRoute  = $this->createDungeonRoute($this->findMappingVersion($inheritedDungeon, GameVersion::GAME_VERSION_CLASSIC_ERA));
        $routeIds                  = [$overriddenClassicEraRoute->id, $overriddenSodRoute->id, $inheritedClassicEraRoute->id];

        // Act
        $idsForSod        = $this->usableRouteIds($this->gameVersion(GameVersion::GAME_VERSION_SOD), $routeIds);
        $idsForTbc        = $this->usableRouteIds($this->gameVersion(GameVersion::GAME_VERSION_TBC), $routeIds);
        $idsForClassicEra = $this->usableRouteIds($this->gameVersion(GameVersion::GAME_VERSION_CLASSIC_ERA), $routeIds);

        // Assert
        $this->assertEqualsCanonicalizing([$overriddenSodRoute->id, $inheritedClassicEraRoute->id], $idsForSod);
        $this->assertEqualsCanonicalizing([$overriddenClassicEraRoute->id, $inheritedClassicEraRoute->id], $idsForTbc);
        $this->assertEqualsCanonicalizing([$overriddenClassicEraRoute->id, $inheritedClassicEraRoute->id], $idsForClassicEra);
    }

    #[Test]
    public function mayContainDungeonRoute_givenChildCollectionAndRouteOnOverriddenParentMappingVersion_returnsFalse(): void
    {
        // Arrange
        $dungeon = $this->createClassicEraDungeon();
        $this->createMappingVersion($dungeon, GameVersion::GAME_VERSION_SOD);
        $collection = DungeonRouteCollection::factory()->make([
            'game_version_id' => GameVersion::ALL[GameVersion::GAME_VERSION_SOD],
            'season_id'       => null,
        ]);
        $dungeonRoute = new DungeonRoute();
        $dungeonRoute->setRelation('mappingVersion', $this->findMappingVersion($dungeon, GameVersion::GAME_VERSION_CLASSIC_ERA));

        // Act
        $mayContain = $collection->mayContainDungeonRoute($dungeonRoute);

        // Assert
        $this->assertFalse($mayContain);
    }

    #[Test]
    public function mayContainDungeonRoute_givenChildCollectionAndRouteOnParentMappingVersion_returnsTrue(): void
    {
        // Arrange
        $collection = DungeonRouteCollection::factory()->make([
            'game_version_id' => GameVersion::ALL[GameVersion::GAME_VERSION_SOD],
            'season_id'       => null,
        ]);
        $dungeonRoute = new DungeonRoute();
        $dungeonRoute->setRelation('mappingVersion', $this->makeMappingVersion(GameVersion::GAME_VERSION_CLASSIC_ERA));

        // Act
        $mayContain = $collection->mayContainDungeonRoute($dungeonRoute);

        // Assert
        $this->assertTrue($mayContain);
    }

    #[Test]
    public function mayContainDungeonRoute_givenParentCollectionAndRouteOnChildMappingVersion_returnsFalse(): void
    {
        // Arrange
        $collection = DungeonRouteCollection::factory()->make([
            'game_version_id' => GameVersion::ALL[GameVersion::GAME_VERSION_CLASSIC_ERA],
            'season_id'       => null,
        ]);
        $dungeonRoute = new DungeonRoute();
        $dungeonRoute->setRelation('mappingVersion', $this->makeMappingVersion(GameVersion::GAME_VERSION_SOD));

        // Act
        $mayContain = $collection->mayContainDungeonRoute($dungeonRoute);

        // Assert
        $this->assertFalse($mayContain);
    }

    #[Test]
    public function getHealthByGameVersion_givenParentHealthOnly_returnsParentHealth(): void
    {
        // Arrange
        $npc = $this->createNpcInDatabase(['game_version_id' => GameVersion::ALL[GameVersion::GAME_VERSION_CLASSIC_ERA]]);
        $this->createNpcHealth($npc->id, GameVersion::GAME_VERSION_CLASSIC_ERA, 1000);
        $npc->load('npcHealths');

        // Act
        $forTbc = $npc->getHealthByGameVersion($this->gameVersion(GameVersion::GAME_VERSION_TBC));

        // Assert
        $this->assertSame(1000, $forTbc?->health);
    }

    #[Test]
    public function getHealthByGameVersion_givenChildAndParentHealth_returnsChildHealth(): void
    {
        // Arrange
        $npc = $this->createNpcInDatabase(['game_version_id' => GameVersion::ALL[GameVersion::GAME_VERSION_CLASSIC_ERA]]);
        $this->createNpcHealth($npc->id, GameVersion::GAME_VERSION_CLASSIC_ERA, 1000);
        $this->createNpcHealth($npc->id, GameVersion::GAME_VERSION_SOD, 2500);
        $npc->load('npcHealths');

        // Act
        $forSod        = $npc->getHealthByGameVersion($this->gameVersion(GameVersion::GAME_VERSION_SOD));
        $forClassicEra = $npc->getHealthByGameVersion($this->gameVersion(GameVersion::GAME_VERSION_CLASSIC_ERA));

        // Assert
        $this->assertSame(2500, $forSod?->health);
        $this->assertSame(1000, $forClassicEra?->health);
    }

    #[Test]
    public function getHealthByGameVersion_givenSiblingHealthOnly_returnsNull(): void
    {
        // Arrange
        $npc = $this->createNpcInDatabase(['game_version_id' => GameVersion::ALL[GameVersion::GAME_VERSION_CLASSIC_ERA]]);
        $this->createNpcHealth($npc->id, GameVersion::GAME_VERSION_SOD, 2500);
        $npc->load('npcHealths');

        // Act
        $forTbc = $npc->getHealthByGameVersion($this->gameVersion(GameVersion::GAME_VERSION_TBC));

        // Assert
        $this->assertNull($forTbc);
    }

    #[Test]
    public function viewDungeon_givenChildGameVersionAndParentOnlyDungeon_redirectsToItsFloor(): void
    {
        // Arrange
        $dungeon = $this->createDungeon(
            ['expansion_id' => Expansion::ALL[Expansion::EXPANSION_CLASSIC], 'active' => true],
            mappingVersionAttributes: ['game_version_id' => GameVersion::ALL[GameVersion::GAME_VERSION_CLASSIC_ERA]],
        );
        $sod = $this->gameVersion(GameVersion::GAME_VERSION_SOD);

        // Act
        $response = $this->get(route('dungeon.explore.gameversion.view', ['gameVersion' => $sod, 'dungeon' => $dungeon]));

        // Assert
        $response->assertRedirect(route('dungeon.explore.gameversion.view.floor', [
            'gameVersion' => $sod,
            'dungeon'     => $dungeon,
            'floorIndex'  => $dungeon->floors()->firstOrFail()->index,
        ]));
    }

    #[Test]
    public function seededNpcHealths_givenEnemiesOnTbcAndSodMappingVersions_haveHealthOfTheMappingVersionsGameVersion(): void
    {
        // Arrange
        $classicEraId = GameVersion::ALL[GameVersion::GAME_VERSION_CLASSIC_ERA];

        // Act
        $npcIdsWithOnlyClassicEraHealth = [];
        foreach ([GameVersion::GAME_VERSION_TBC, GameVersion::GAME_VERSION_SOD] as $gameVersionKey) {
            $gameVersionId = GameVersion::ALL[$gameVersionKey];

            $npcIdsWithOnlyClassicEraHealth[$gameVersionKey] = Enemy::query()
                ->whereHas('mappingVersion', static fn(Builder $query) => $query->where('game_version_id', $gameVersionId))
                ->whereHas('npc.npcHealths', static fn(Builder $query) => $query->where('game_version_id', $classicEraId))
                ->whereDoesntHave('npc.npcHealths', static fn(Builder $query) => $query->where('game_version_id', $gameVersionId))
                ->distinct()
                ->pluck('npc_id')
                ->all();
        }

        // Assert
        $this->assertSame([GameVersion::GAME_VERSION_TBC => [], GameVersion::GAME_VERSION_SOD => []], $npcIdsWithOnlyClassicEraHealth);
    }

    private function gameVersion(string $key): GameVersion
    {
        return GameVersion::query()->where('key', $key)->firstOrFail();
    }

    private function createClassicEraDungeon(): Dungeon
    {
        return $this->createDungeon(
            ['expansion_id' => Expansion::ALL[Expansion::EXPANSION_CLASSIC]],
            mappingVersionAttributes: ['game_version_id' => GameVersion::ALL[GameVersion::GAME_VERSION_CLASSIC_ERA]],
        );
    }

    private function createMappingVersion(Dungeon $dungeon, string $gameVersionKey, int $version = 1): MappingVersion
    {
        return MappingVersion::create([
            'game_version_id'                 => GameVersion::ALL[$gameVersionKey],
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

    private function findMappingVersion(Dungeon $dungeon, string $gameVersionKey): MappingVersion
    {
        return MappingVersion::query()
            ->where('dungeon_id', $dungeon->id)
            ->where('game_version_id', GameVersion::ALL[$gameVersionKey])
            ->firstOrFail();
    }

    private function createDungeonRoute(MappingVersion $mappingVersion): DungeonRoute
    {
        $dungeonRoute = DungeonRoute::factory()->create([
            'dungeon_id'         => $mappingVersion->dungeon_id,
            'mapping_version_id' => $mappingVersion->id,
            'season_id'          => null,
        ]);

        $this->beforeApplicationDestroyed(static fn() => $dungeonRoute->delete());

        return $dungeonRoute;
    }

    /**
     * @param  array<int, int> $routeIds
     * @return array<int, int>
     */
    private function usableRouteIds(GameVersion $gameVersion, array $routeIds): array
    {
        $query = DungeonRoute::query()
            ->join('mapping_versions', 'mapping_versions.id', 'dungeon_routes.mapping_version_id')
            ->whereIn('dungeon_routes.id', $routeIds);

        return app(GameVersionRepositoryInterface::class)->whereMappingVersionIsUsable($gameVersion, $query)->pluck('dungeon_routes.id')->all();
    }

    private function canUseMappingVersion(GameVersion $gameVersion, MappingVersion $mappingVersion): bool
    {
        return app(GameVersionRepositoryInterface::class)->canUseMappingVersion($gameVersion, $mappingVersion);
    }

    private function makeMappingVersion(string $gameVersionKey): MappingVersion
    {
        return new MappingVersion(['game_version_id' => GameVersion::ALL[$gameVersionKey]]);
    }

    private function createNpcHealth(int $npcId, string $gameVersionKey, int $health): void
    {
        $npcHealth = NpcHealth::create([
            'npc_id'          => $npcId,
            'game_version_id' => GameVersion::ALL[$gameVersionKey],
            'health'          => $health,
            'percentage'      => null,
        ]);

        // Npc::deleting leaves an NPC's healths behind
        $this->beforeApplicationDestroyed(static fn() => $npcHealth->delete());
    }
}
