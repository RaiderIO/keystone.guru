<?php

namespace Tests\Feature\App\Model\Mapping;

use App\Models\Dungeon;
use App\Models\MapIcon;
use App\Models\MapIconType;
use App\Models\MapObjectToAwakenedObeliskLink;
use App\Models\Mapping\MappingVersion;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

/**
 * Guards #3873: {@see MappingVersion::boot()}'s `deleting` listener used to mass-delete its
 * {@see MapIcon} relation via the query builder, which never fires `MapIcon::deleting` (added by
 * the `HasLinkedAwakenedObelisk` trait) and silently orphaned any linked obelisk row.
 */
#[Group('MappingVersion')]
final class MappingVersionDeletingCascadesMapIconLinkedObeliskTest extends PublicTestCase
{
    #[Test]
    public function delete_givenMappingVersionWithMapIconLinkedToAnAwakenedObelisk_deletesTheLinkThroughItsOwnDeletingHook(): void
    {
        // Arrange
        /** @var Dungeon $dungeon */
        $dungeon = Dungeon::whereNotNull('challenge_mode_id')->firstOrFail();

        $existingMappingVersion = $dungeon->getCurrentMappingVersion();

        $mappingVersion = MappingVersion::create([
            'game_version_id'                 => $existingMappingVersion->game_version_id,
            'dungeon_id'                      => $dungeon->id,
            'version'                         => $existingMappingVersion->version + 1000,
            'enemy_forces_required'           => $existingMappingVersion->enemy_forces_required,
            'enemy_forces_required_teeming'   => $existingMappingVersion->enemy_forces_required_teeming,
            'enemy_forces_shrouded'           => $existingMappingVersion->enemy_forces_shrouded,
            'enemy_forces_shrouded_zul_gamux' => $existingMappingVersion->enemy_forces_shrouded_zul_gamux,
            'timer_max_seconds'               => $existingMappingVersion->timer_max_seconds,
            'facade_enabled'                  => false,
        ]);

        $mapIcon = MapIcon::factory()->create([
            'mapping_version_id' => $mappingVersion->id,
            'dungeon_route_id'   => null,
            'floor_id'           => $dungeon->floors->first()->id,
            'map_icon_type_id'   => MapIconType::ALL[MapIconType::MAP_ICON_TYPE_AWAKENED_OBELISK_ENTROPIC],
        ]);

        $link = MapObjectToAwakenedObeliskLink::create([
            'source_map_object_id'           => $mapIcon->id,
            'source_map_object_class_name'   => MapIcon::class,
            'target_map_icon_type_id'        => MapIconType::ALL[MapIconType::MAP_ICON_TYPE_AWAKENED_OBELISK_DEFILED],
            'target_map_icon_seasonal_index' => 0,
        ]);

        try {
            // Act
            $mappingVersion->delete();

            // Assert
            $this->assertDatabaseMissing('map_icons', ['id' => $mapIcon->id]);
            $this->assertDatabaseMissing('map_object_to_awakened_obelisk_links', ['id' => $link->id]);
        } finally {
            MapObjectToAwakenedObeliskLink::query()->where('id', $link->id)->delete();
            MapIcon::query()->where('id', $mapIcon->id)->delete();
            MappingVersion::query()->where('id', $mappingVersion->id)->delete();
        }
    }
}
