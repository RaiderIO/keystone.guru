<?php

namespace Tests\Feature\App\Service\KillZonePath;

use App\Models\Dungeon;
use App\Models\DungeonFloorSwitchMarker;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Floor\Floor;
use App\Models\KillZone\KillZone;
use App\Models\Mapping\MappingVersion;
use App\Service\KillZonePath\KillZonePathServiceInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('KillZonePath')]
final class KillZonePathServiceTest extends PublicTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        config(['broadcasting.default' => 'null']);
    }

    /**
     * Creates a fresh, empty MappingVersion for a dungeon that has at least 2 non-facade floors.
     * Returns [dungeon, freshMappingVersion, floor1, floor2].
     * The caller is responsible for deleting the MappingVersion in finally.
     *
     * @return array{Dungeon, MappingVersion, Floor, Floor}
     */
    private function createFreshMappingVersionWithTwoFloors(): array
    {
        /** @var Dungeon|null $dungeon */
        $dungeon = Dungeon::whereNotNull('challenge_mode_id')
            ->get()
            ->first(static function (Dungeon $dungeon): bool {
                $mappingVersion = $dungeon->getCurrentMappingVersion();
                if ($mappingVersion === null || $mappingVersion->facade_enabled) {
                    return false;
                }

                return $dungeon->floors()->where('facade', false)->count() >= 2;
            });

        if ($dungeon === null) {
            $this->fail('No dungeon found with 2+ non-facade floors and a non-facade mapping version');
        }

        $existingMV = $dungeon->getCurrentMappingVersion();

        // Create an isolated mapping version with no seeded DFSMs
        $freshMappingVersion = MappingVersion::create([
            'game_version_id'                 => $existingMV->game_version_id,
            'dungeon_id'                      => $dungeon->id,
            'version'                         => $existingMV->version + 1000,
            'enemy_forces_required'           => $existingMV->enemy_forces_required,
            'enemy_forces_required_teeming'   => $existingMV->enemy_forces_required_teeming,
            'enemy_forces_shrouded'           => $existingMV->enemy_forces_shrouded,
            'enemy_forces_shrouded_zul_gamux' => $existingMV->enemy_forces_shrouded_zul_gamux,
            'timer_max_seconds'               => $existingMV->timer_max_seconds,
            'facade_enabled'                  => false,
        ]);

        /** @var Floor[] $floors */
        $floors = $dungeon->floors()->where('facade', false)->take(2)->get();

        return [$dungeon, $freshMappingVersion, $floors[0], $floors[1]];
    }

    #[Test]
    public function findPathsToKillZones_givenTwoWayLinkedMarkers_returnsPathAcrossFloors(): void
    {
        // Arrange
        [$dungeon, $mappingVersion, $floor1, $floor2] = $this->createFreshMappingVersionWithTwoFloors();

        $dungeonRoute = DungeonRoute::factory()->create([
            'dungeon_id'         => $dungeon->id,
            'mapping_version_id' => $mappingVersion->id,
        ]);

        /** @var DungeonFloorSwitchMarker $markerA */
        $markerA = DungeonFloorSwitchMarker::create([
            'mapping_version_id'                    => $mappingVersion->id,
            'floor_id'                              => $floor1->id,
            'target_floor_id'                       => $floor2->id,
            'source_floor_id'                       => null,
            'linked_dungeon_floor_switch_marker_id' => null,
            'direction'                             => null,
            'hidden_in_facade'                      => false,
            'lat'                                   => -128.0,
            'lng'                                   => 128.0,
        ]);
        /** @var DungeonFloorSwitchMarker $markerB */
        $markerB = DungeonFloorSwitchMarker::create([
            'mapping_version_id'                    => $mappingVersion->id,
            'floor_id'                              => $floor2->id,
            'target_floor_id'                       => $floor1->id,
            'source_floor_id'                       => null,
            'linked_dungeon_floor_switch_marker_id' => $markerA->id,
            'direction'                             => null,
            'hidden_in_facade'                      => false,
            'lat'                                   => -192.0,
            'lng'                                   => 192.0,
        ]);
        $markerA->update(['linked_dungeon_floor_switch_marker_id' => $markerB->id]);

        $killZone1 = KillZone::factory()->create([
            'dungeon_route_id' => $dungeonRoute->id,
            'floor_id'         => $floor1->id,
            'lat'              => -100.0,
            'lng'              => 100.0,
            'index'            => 1,
        ]);
        $killZone2 = KillZone::factory()->create([
            'dungeon_route_id' => $dungeonRoute->id,
            'floor_id'         => $floor2->id,
            'lat'              => -200.0,
            'lng'              => 200.0,
            'index'            => 2,
        ]);

        try {
            // Act
            /** @var KillZonePathServiceInterface $service */
            $service = app(KillZonePathServiceInterface::class);
            $result  = $service->findPathsToKillZones($dungeonRoute);

            // Assert - path from floor1 kill zone to floor2 kill zone must exist
            $this->assertArrayHasKey($killZone2->id, $result);
            $this->assertNotEmpty(
                $result[$killZone2->id],
                'Expected a cross-floor path when both markers are linked (two-way)',
            );
        } finally {
            $killZone2->delete();
            $killZone1->delete();
            $markerB->delete();
            $markerA->delete();
            $dungeonRoute->delete();
            $mappingVersion->delete();
        }
    }

    #[Test]
    public function findPathsToKillZones_givenOneWayMarkerWithNullLinkedId_returnsEmptyPathForBlockedDirection(): void
    {
        // Arrange
        [$dungeon, $mappingVersion, $floor1, $floor2] = $this->createFreshMappingVersionWithTwoFloors();

        $dungeonRoute = DungeonRoute::factory()->create([
            'dungeon_id'         => $dungeon->id,
            'mapping_version_id' => $mappingVersion->id,
        ]);

        // markerA (floor1→floor2) has a link, so pathfinding can traverse forward.
        // markerB (floor2→floor1) has linked_dungeon_floor_switch_marker_id = null, making it
        // one-way: no cross-floor edge is added from markerB, so the backward direction is blocked.
        $markerA = DungeonFloorSwitchMarker::create([
            'mapping_version_id'                    => $mappingVersion->id,
            'floor_id'                              => $floor1->id,
            'target_floor_id'                       => $floor2->id,
            'source_floor_id'                       => null,
            'linked_dungeon_floor_switch_marker_id' => null,
            'direction'                             => null,
            'hidden_in_facade'                      => false,
            'lat'                                   => -128.0,
            'lng'                                   => 128.0,
        ]);
        $markerB = DungeonFloorSwitchMarker::create([
            'mapping_version_id'                    => $mappingVersion->id,
            'floor_id'                              => $floor2->id,
            'target_floor_id'                       => $floor1->id,
            'source_floor_id'                       => null,
            'linked_dungeon_floor_switch_marker_id' => null,
            'direction'                             => null,
            'hidden_in_facade'                      => false,
            'lat'                                   => -192.0,
            'lng'                                   => 192.0,
        ]);
        $markerA->update(['linked_dungeon_floor_switch_marker_id' => $markerB->id]);

        // Kill zones in reverse order: KZ1 on floor2, KZ2 on floor1 — attempting floor2→floor1
        $killZone1 = KillZone::factory()->create([
            'dungeon_route_id' => $dungeonRoute->id,
            'floor_id'         => $floor2->id,
            'lat'              => -200.0,
            'lng'              => 200.0,
            'index'            => 1,
        ]);
        $killZone2 = KillZone::factory()->create([
            'dungeon_route_id' => $dungeonRoute->id,
            'floor_id'         => $floor1->id,
            'lat'              => -100.0,
            'lng'              => 100.0,
            'index'            => 2,
        ]);

        try {
            // Act
            /** @var KillZonePathServiceInterface $service */
            $service = app(KillZonePathServiceInterface::class);
            $result  = $service->findPathsToKillZones($dungeonRoute);

            // Assert - no cross-floor path when markerB has null linked_dungeon_floor_switch_marker_id
            $this->assertArrayHasKey($killZone2->id, $result);
            $this->assertEmpty(
                $result[$killZone2->id],
                'Expected no cross-floor path when the backward marker has null linked_dungeon_floor_switch_marker_id (one-way)',
            );
        } finally {
            $killZone2->delete();
            $killZone1->delete();
            $markerB->delete();
            $markerA->delete();
            $dungeonRoute->delete();
            $mappingVersion->delete();
        }
    }
}
