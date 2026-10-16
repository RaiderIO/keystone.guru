<?php

namespace Tests\Feature\App\Service\MDT;

use App\Models\Dungeon;
use App\Models\DungeonFloorSwitchMarker;
use App\Models\Enemy;
use App\Models\EnemyPatrol;
use App\Models\Faction;
use App\Models\Floor\Floor;
use App\Models\Floor\FloorCoupling;
use App\Models\Mapping\MappingVersion;
use App\Models\Npc\Npc;
use App\Models\Npc\NpcDungeon;
use App\Models\Polyline;
use App\Service\MDT\Lua\LuaTableParser;
use App\Service\MDT\MDTMappingExportServiceInterface;
use Database\Factories\FloorFactory;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\Traits\CreatesDungeon;
use Tests\Fixtures\Traits\CreatesNpc;
use Tests\TestCases\PublicTestCase;

#[Group('MDT')]
final class MDTMappingExportServiceTest extends PublicTestCase
{
    use CreatesDungeon;
    use CreatesNpc;

    #[Test]
    public function getMDTMappingAsLuaString_givenEnemyOnFloorWithMdtSubLevel_exportsTheEnemyOnThatSubLevel(): void
    {
        // Arrange
        $dungeon        = $this->createDungeon();
        $mappingVersion = $dungeon->getCurrentMappingVersion();
        $floor          = $this->createFloor($dungeon, index: 2, mdtSubLevel: 3);
        $npc            = $this->createNpcForDungeon($dungeon);
        $this->createEnemy($mappingVersion, $floor, $npc, mdtId: 1);

        // Act
        $clones = $this->getExportedClones($mappingVersion, $npc);

        // Assert
        $this->assertSame('3', (string)$clones[1]['sublevel']);
    }

    #[Test]
    public function getMDTMappingAsLuaString_givenEnemyOnFloorWithoutMdtSubLevel_exportsTheEnemyOnTheFloorIndex(): void
    {
        // Arrange
        $dungeon        = $this->createDungeon();
        $mappingVersion = $dungeon->getCurrentMappingVersion();
        $floor          = $this->createFloor($dungeon, index: 2, mdtSubLevel: null);
        $npc            = $this->createNpcForDungeon($dungeon);
        $this->createEnemy($mappingVersion, $floor, $npc, mdtId: 1);

        // Act
        $clones = $this->getExportedClones($mappingVersion, $npc);

        // Assert
        $this->assertSame('2', (string)$clones[1]['sublevel']);
    }

    #[Test]
    public function getMDTMappingAsLuaString_givenLinkedFloorSwitchMarkers_givesBothEndsOfEachLinkTheSameConnectionIndex(): void
    {
        // Arrange - floor 2 holds two links, so the second one sits at position 2 on floor 2 but position 1 on floor 3
        $dungeon        = $this->createDungeon();
        $mappingVersion = $dungeon->getCurrentMappingVersion();
        $firstFloor     = $dungeon->floors()->firstOrFail();
        $secondFloor    = $this->createFloor($dungeon, index: 2, mdtSubLevel: null);
        $thirdFloor     = $this->createFloor($dungeon, index: 3, mdtSubLevel: null);
        $this->createLinkedFloorSwitchMarkers($mappingVersion, $firstFloor, $secondFloor);
        $this->createLinkedFloorSwitchMarkers($mappingVersion, $secondFloor, $thirdFloor);

        // Act
        $mapPOIs = $this->getExportedLua($mappingVersion, regenerateMapPOIs: true)['mapPOIs'];

        // Assert
        $firstToSecond = $this->getMapLinkConnectionIndex($mapPOIs, subLevel: 1, target: 2);
        $secondToFirst = $this->getMapLinkConnectionIndex($mapPOIs, subLevel: 2, target: 1);
        $secondToThird = $this->getMapLinkConnectionIndex($mapPOIs, subLevel: 2, target: 3);
        $thirdToSecond = $this->getMapLinkConnectionIndex($mapPOIs, subLevel: 3, target: 2);

        $this->assertSame($firstToSecond, $secondToFirst);
        $this->assertSame($secondToThird, $thirdToSecond);
        $this->assertNotSame($firstToSecond, $secondToThird);
    }

    #[Test]
    public function getMDTMappingAsLuaString_givenUnlinkedFloorSwitchMarkers_givesEachItsOwnConnectionIndex(): void
    {
        // Arrange
        $dungeon        = $this->createDungeon();
        $mappingVersion = $dungeon->getCurrentMappingVersion();
        $firstFloor     = $dungeon->floors()->firstOrFail();
        $secondFloor    = $this->createFloor($dungeon, index: 2, mdtSubLevel: null);
        $this->createFloorSwitchMarker($mappingVersion, $firstFloor, $secondFloor);
        $this->createFloorSwitchMarker($mappingVersion, $secondFloor, $firstFloor);

        // Act
        $mapPOIs = $this->getExportedLua($mappingVersion, regenerateMapPOIs: true)['mapPOIs'];

        // Assert
        $this->assertNotSame(
            $this->getMapLinkConnectionIndex($mapPOIs, subLevel: 1, target: 2),
            $this->getMapLinkConnectionIndex($mapPOIs, subLevel: 2, target: 1),
        );
    }

    #[Test]
    public function getMDTMappingAsLuaString_givenPatrolDrawnInTheEditor_exportsItOnTheFirstOfItsEnemies(): void
    {
        // Arrange - an editor patrol carries no MDT ids
        $dungeon        = $this->createDungeon();
        $mappingVersion = $dungeon->getCurrentMappingVersion();
        $floor          = $dungeon->floors()->firstOrFail();
        $npc            = $this->createNpcForDungeon($dungeon);
        $enemyPatrol    = $this->createEnemyPatrol($mappingVersion, $floor);
        $this->createEnemy($mappingVersion, $floor, $npc, mdtId: 2, enemyPatrol: $enemyPatrol);
        $this->createEnemy($mappingVersion, $floor, $npc, mdtId: 1, enemyPatrol: $enemyPatrol);

        // Act
        $clones = $this->getExportedClones($mappingVersion, $npc);

        // Assert - MDT does not store the closing vertex, so three vertices export as two
        $this->assertCount(2, $clones[1]['patrol'] ?? []);
        $this->assertArrayNotHasKey('patrol', $clones[2]);
    }

    #[Test]
    public function getMDTMappingAsLuaString_givenEditorPatrolImportedBackOntoItsOwner_exportsTheSameLua(): void
    {
        // Arrange - an MDT import stores the clone a patrol came from as the patrol's MDT ids
        $dungeon        = $this->createDungeon();
        $mappingVersion = $dungeon->getCurrentMappingVersion();
        $floor          = $dungeon->floors()->firstOrFail();
        $npc            = $this->createNpcForDungeon($dungeon);
        $enemyPatrol    = $this->createEnemyPatrol($mappingVersion, $floor);
        $this->createEnemy($mappingVersion, $floor, $npc, mdtId: 2, enemyPatrol: $enemyPatrol);
        $this->createEnemy($mappingVersion, $floor, $npc, mdtId: 1, enemyPatrol: $enemyPatrol);
        $editorLua = $this->getExportedLuaString($mappingVersion);

        $enemyPatrol->update(['mdt_npc_id' => $npc->id, 'mdt_id' => 1]);

        // Act
        $importedLua = $this->getExportedLuaString($mappingVersion);

        // Assert
        $this->assertSame($editorLua, $importedLua);
    }

    #[Test]
    public function getMDTMappingAsLuaString_givenPatrolImportedFromMDT_exportsItOnTheCloneItCameFrom(): void
    {
        // Arrange
        $dungeon        = $this->createDungeon();
        $mappingVersion = $dungeon->getCurrentMappingVersion();
        $floor          = $dungeon->floors()->firstOrFail();
        $npc            = $this->createNpcForDungeon($dungeon);
        $enemyPatrol    = $this->createEnemyPatrol($mappingVersion, $floor);
        $enemyPatrol->update(['mdt_npc_id' => $npc->id, 'mdt_id' => 2]);
        $this->createEnemy($mappingVersion, $floor, $npc, mdtId: 1, enemyPatrol: $enemyPatrol);
        $this->createEnemy($mappingVersion, $floor, $npc, mdtId: 2, enemyPatrol: $enemyPatrol);

        // Act
        $clones = $this->getExportedClones($mappingVersion, $npc);

        // Assert
        $this->assertArrayNotHasKey('patrol', $clones[1]);
        $this->assertCount(2, $clones[2]['patrol'] ?? []);
    }

    private function createFloor(Dungeon $dungeon, int $index, ?int $mdtSubLevel): Floor
    {
        return FloorFactory::new()->create([
            'dungeon_id'    => $dungeon->id,
            'index'         => $index,
            'mdt_sub_level' => $mdtSubLevel,
            'name'          => sprintf('Test Floor %d', $index),
            'default'       => false,
        ]);
    }

    private function createNpcForDungeon(Dungeon $dungeon): Npc
    {
        $npc = $this->createNpcInDatabase();

        NpcDungeon::create([
            'npc_id'     => $npc->id,
            'dungeon_id' => $dungeon->id,
        ]);

        return $npc;
    }

    private function createEnemy(
        MappingVersion $mappingVersion,
        Floor          $floor,
        Npc            $npc,
        int            $mdtId,
        ?EnemyPatrol   $enemyPatrol = null,
    ): Enemy {
        return Enemy::create([
            'mapping_version_id' => $mappingVersion->id,
            'floor_id'           => $floor->id,
            'enemy_patrol_id'    => $enemyPatrol?->id,
            'npc_id'             => $npc->id,
            'mdt_id'             => $mdtId,
            'faction'            => Faction::FACTION_ANY,
            'lat'                => -100.0 - $mdtId,
            'lng'                => 100.0 + $mdtId,
        ]);
    }

    private function createEnemyPatrol(MappingVersion $mappingVersion, Floor $floor): EnemyPatrol
    {
        $enemyPatrol = EnemyPatrol::create([
            'mapping_version_id' => $mappingVersion->id,
            'floor_id'           => $floor->id,
            'polyline_id'        => -1,
            'teeming'            => null,
            'faction'            => Faction::FACTION_ANY,
        ]);

        $polyline = Polyline::create([
            'model_id'       => $enemyPatrol->id,
            'model_class'    => EnemyPatrol::class,
            'color'          => '#003280',
            'color_animated' => null,
            'weight'         => 2,
            'vertices_json'  => json_encode([
                ['lat' => -100, 'lng' => 100],
                ['lat' => -120, 'lng' => 120],
                ['lat' => -100, 'lng' => 100],
            ]),
        ]);

        $enemyPatrol->update(['polyline_id' => $polyline->id]);

        return $enemyPatrol;
    }

    private function createLinkedFloorSwitchMarkers(MappingVersion $mappingVersion, Floor $floor, Floor $targetFloor): void
    {
        $there = $this->createFloorSwitchMarker($mappingVersion, $floor, $targetFloor);
        $back  = $this->createFloorSwitchMarker($mappingVersion, $targetFloor, $floor);

        $there->update(['linked_dungeon_floor_switch_marker_id' => $back->id]);
        $back->update(['linked_dungeon_floor_switch_marker_id' => $there->id]);
    }

    private function createFloorSwitchMarker(MappingVersion $mappingVersion, Floor $floor, Floor $targetFloor): DungeonFloorSwitchMarker
    {
        return DungeonFloorSwitchMarker::create([
            'mapping_version_id' => $mappingVersion->id,
            'floor_id'           => $floor->id,
            'source_floor_id'    => $floor->id,
            'target_floor_id'    => $targetFloor->id,
            'direction'          => FloorCoupling::DIRECTION_UP,
            'lat'                => -150.0,
            'lng'                => 150.0,
        ]);
    }

    /**
     * @param array<int, array<int, array<string, mixed>>> $mapPOIs
     */
    private function getMapLinkConnectionIndex(array $mapPOIs, int $subLevel, int $target): string
    {
        $mapLinks = array_values(array_filter(
            $mapPOIs[$subLevel] ?? [],
            static fn(array $mapPOI) => (string)$mapPOI['type'] === '"mapLink"' && (string)$mapPOI['target'] === (string)$target,
        ));

        $this->assertCount(1, $mapLinks, sprintf('Expected one map link from sublevel %d to %d', $subLevel, $target));

        return (string)$mapLinks[0]['connectionIndex'];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getExportedClones(MappingVersion $mappingVersion, Npc $npc): array
    {
        $dungeonEnemies = array_values(array_filter(
            $this->getExportedLua($mappingVersion)['dungeonEnemies'],
            static fn(array $dungeonEnemy) => (string)$dungeonEnemy['id'] === (string)$npc->id,
        ));

        $this->assertCount(1, $dungeonEnemies);

        return $dungeonEnemies[0]['clones'];
    }

    /**
     * @return array<string, mixed>
     */
    private function getExportedLua(MappingVersion $mappingVersion, bool $regenerateMapPOIs = false): array
    {
        return new LuaTableParser($this->getExportedLuaString($mappingVersion, $regenerateMapPOIs))
            ->parseDungeonIndexAssignments();
    }

    private function getExportedLuaString(MappingVersion $mappingVersion, bool $regenerateMapPOIs = false): string
    {
        return app(MDTMappingExportServiceInterface::class)->getMDTMappingAsLuaString(
            $mappingVersion->fresh(),
            excludeTranslations: true,
            regenerateMapPOIs: $regenerateMapPOIs,
        );
    }
}
