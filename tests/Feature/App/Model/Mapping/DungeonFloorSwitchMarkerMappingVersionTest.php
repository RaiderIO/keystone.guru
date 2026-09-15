<?php

namespace Tests\Feature\App\Model\Mapping;

use App\Models\Dungeon;
use App\Models\DungeonFloorSwitchMarker;
use App\Models\Mapping\MappingVersion;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('MappingVersion')]
#[Group('DungeonFloorSwitchMarker')]
final class DungeonFloorSwitchMarkerMappingVersionTest extends PublicTestCase
{
    #[Test]
    public function create_givenMappingVersionWithLinkedDungeonFloorSwitchMarkers_relinksClonedMarkersToEachOther(): void
    {
        // Arrange
        $existingMappingVersion = $this->getMappingVersionThatWillBeCloned();

        /** @var DungeonFloorSwitchMarker $marker */
        $marker = $existingMappingVersion->dungeonFloorSwitchMarkers()->firstOrFail();

        // Sentinel lat/lng values that don't occur in the seeded mapping so the clones can be
        // found unambiguously afterwards.
        $sentinelLatA = 111.11;
        $sentinelLatB = 222.22;

        $markerA = DungeonFloorSwitchMarker::create([
            'mapping_version_id' => $existingMappingVersion->id,
            'floor_id'           => $marker->floor_id,
            'source_floor_id'    => $marker->source_floor_id,
            'target_floor_id'    => $marker->target_floor_id,
            'direction'          => $marker->direction,
            'hidden_in_facade'   => false,
            'lat'                => $sentinelLatA,
            'lng'                => $marker->lng,
        ]);
        $markerB = DungeonFloorSwitchMarker::create([
            'mapping_version_id' => $existingMappingVersion->id,
            'floor_id'           => $marker->floor_id,
            'source_floor_id'    => $marker->source_floor_id,
            'target_floor_id'    => $marker->target_floor_id,
            'direction'          => $marker->direction,
            'hidden_in_facade'   => false,
            'lat'                => $sentinelLatB,
            'lng'                => $marker->lng,
        ]);

        $markerA->update(['linked_dungeon_floor_switch_marker_id' => $markerB->id]);
        $markerB->update(['linked_dungeon_floor_switch_marker_id' => $markerA->id]);

        $newMappingVersion = null;

        try {
            // Act
            $newMappingVersion = $this->createNextMappingVersion($existingMappingVersion);

            // Assert
            /** @var DungeonFloorSwitchMarker|null $clonedA */
            $clonedA = DungeonFloorSwitchMarker::where('mapping_version_id', $newMappingVersion->id)
                ->where('lat', $sentinelLatA)
                ->first();
            /** @var DungeonFloorSwitchMarker|null $clonedB */
            $clonedB = DungeonFloorSwitchMarker::where('mapping_version_id', $newMappingVersion->id)
                ->where('lat', $sentinelLatB)
                ->first();

            $this->assertNotNull($clonedA, 'Marker A must have been cloned into the new MappingVersion.');
            $this->assertNotNull($clonedB, 'Marker B must have been cloned into the new MappingVersion.');

            $this->assertNotSame($markerA->id, $clonedA->id, 'The clone must be a new row.');
            $this->assertNotSame($markerB->id, $clonedB->id, 'The clone must be a new row.');

            // This is the part that silently breaks: without the second-pass FK re-link in
            // MappingVersion::boot() the cloned markers keep pointing at the OLD mapping version's
            // marker ids, so the Facade view can no longer draw the connecting line between them.
            $this->assertSame(
                $clonedB->id,
                $clonedA->linked_dungeon_floor_switch_marker_id,
                'Cloned marker A must link to cloned marker B, not to the previous mapping version\'s marker.',
            );
            $this->assertSame(
                $clonedA->id,
                $clonedB->linked_dungeon_floor_switch_marker_id,
                'Cloned marker B must link to cloned marker A, not to the previous mapping version\'s marker.',
            );
        } finally {
            if ($newMappingVersion !== null) {
                // An Eloquent delete, not a query builder one: MappingVersion::boot()'s `deleting`
                // cascade is what removes everything its `created` hook cloned. A query builder delete
                // fires no model events, so all of that would leak into the shared test database on
                // every run.
                $newMappingVersion->delete();
            }

            DungeonFloorSwitchMarker::whereIn('id', [$markerA->id, $markerB->id])->delete();
        }
    }

    #[Test]
    public function create_givenMappingVersionWithDungeonFloorSwitchMarkerLinkedOutsideItself_clearsLinkOnClone(): void
    {
        // Arrange
        $existingMappingVersion = $this->getMappingVersionThatWillBeCloned();

        /** @var DungeonFloorSwitchMarker $marker */
        $marker = $existingMappingVersion->dungeonFloorSwitchMarkers()->firstOrFail();

        /** @var DungeonFloorSwitchMarker $markerOutsideMappingVersion */
        $markerOutsideMappingVersion = DungeonFloorSwitchMarker::where('mapping_version_id', '!=', $existingMappingVersion->id)
            ->firstOrFail();

        // Sentinel lat value that doesn't occur in the seeded mapping so the clone can be found
        // unambiguously afterwards.
        $sentinelLat = 333.33;

        // Simulate an already-dangling link - e.g. left over from a previous mapping version clone,
        // or the marker it used to point at was deleted without clearing the reverse link.
        $danglingMarker = DungeonFloorSwitchMarker::create([
            'mapping_version_id'                    => $existingMappingVersion->id,
            'floor_id'                              => $marker->floor_id,
            'source_floor_id'                       => $marker->source_floor_id,
            'target_floor_id'                       => $marker->target_floor_id,
            'linked_dungeon_floor_switch_marker_id' => $markerOutsideMappingVersion->id,
            'direction'                             => $marker->direction,
            'hidden_in_facade'                      => false,
            'lat'                                   => $sentinelLat,
            'lng'                                   => $marker->lng,
        ]);

        $newMappingVersion = null;

        try {
            // Act
            $newMappingVersion = $this->createNextMappingVersion($existingMappingVersion);

            // Assert
            /** @var DungeonFloorSwitchMarker|null $clonedMarker */
            $clonedMarker = DungeonFloorSwitchMarker::where('mapping_version_id', $newMappingVersion->id)
                ->where('lat', $sentinelLat)
                ->first();

            $this->assertNotNull($clonedMarker, 'The marker must have been cloned into the new MappingVersion.');

            // Without the null fallback, the clone would keep pointing at $markerOutsideMappingVersion's
            // id - a pointer into a foreign mapping version's id space, indistinguishable from the
            // #4160 corruption.
            $this->assertNull(
                $clonedMarker->linked_dungeon_floor_switch_marker_id,
                'A link that could not be resolved within the cloned mapping version must be cleared, not carried over.',
            );
        } finally {
            if ($newMappingVersion !== null) {
                $newMappingVersion->delete();
            }

            DungeonFloorSwitchMarker::where('id', $danglingMarker->id)->delete();
        }
    }

    /**
     * The mapping version MappingVersion::boot() will clone from - the highest `version` of the
     * dungeon scoped to the same game_version_id as the mapping version being created.
     */
    private function getMappingVersionThatWillBeCloned(): MappingVersion
    {
        /** @var Dungeon|null $dungeon */
        $dungeon = Dungeon::whereNotNull('challenge_mode_id')
            ->get()
            ->first(static function (Dungeon $dungeon): bool {
                /** @var MappingVersion|null $mappingVersion */
                $mappingVersion = $dungeon->mappingVersions()->first();

                return $mappingVersion !== null && $mappingVersion->dungeonFloorSwitchMarkers()->exists();
            });

        if ($dungeon === null) {
            $this->fail('No dungeon with dungeon floor switch markers found for testing.');
        }

        /** @var MappingVersion|null $mappingVersion */
        $mappingVersion = $dungeon->mappingVersions()->first();

        if ($mappingVersion === null) {
            $this->fail('Dungeon has no mapping versions.');
        }

        return $mappingVersion;
    }

    private function createNextMappingVersion(MappingVersion $existingMappingVersion): MappingVersion
    {
        return MappingVersion::create([
            'game_version_id'                 => $existingMappingVersion->game_version_id,
            'dungeon_id'                      => $existingMappingVersion->dungeon_id,
            'version'                         => $existingMappingVersion->version + 1000,
            'enemy_forces_required'           => $existingMappingVersion->enemy_forces_required,
            'enemy_forces_required_teeming'   => $existingMappingVersion->enemy_forces_required_teeming,
            'enemy_forces_shrouded'           => $existingMappingVersion->enemy_forces_shrouded,
            'enemy_forces_shrouded_zul_gamux' => $existingMappingVersion->enemy_forces_shrouded_zul_gamux,
            'timer_max_seconds'               => $existingMappingVersion->timer_max_seconds,
            'facade_enabled'                  => false,
        ]);
    }
}
