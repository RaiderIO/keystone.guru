<?php

namespace Tests\Feature\App\Model\Mapping;

use App\Models\Dungeon;
use App\Models\DungeonStart;
use App\Models\MapIcon;
use App\Models\MapIconType;
use App\Models\Mapping\MappingVersion;
use App\Service\Coordinates\CoordinatesServiceInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('MappingVersion')]
#[Group('DungeonStart')]
final class DungeonStartMappingVersionTest extends PublicTestCase
{
    /** Sentinel lat that doesn't occur in the seeded mapping, so the clone can be found unambiguously */
    private const float SENTINEL_LAT = 111.11;

    #[Test]
    public function create_givenMappingVersionWithDungeonStart_clonesItIntoTheNewMappingVersion(): void
    {
        // Arrange
        $existingMappingVersion = $this->getMappingVersionThatWillBeCloned();
        $floorId                = $existingMappingVersion->dungeon->floors->first()->id;
        $targetDungeonId        = Dungeon::query()->where('id', '!=', $existingMappingVersion->dungeon_id)->value('id');

        $dungeonStart = DungeonStart::factory()->create([
            'mapping_version_id' => $existingMappingVersion->id,
            'floor_id'           => $floorId,
            'target_dungeon_id'  => $targetDungeonId,
            'lat'                => self::SENTINEL_LAT,
            'lng'                => 100.0,
            'comment'            => 'mapping.start.east',
        ]);

        $newMappingVersion = null;

        try {
            // Act
            $newMappingVersion = $this->createNextMappingVersion($existingMappingVersion);

            // Assert
            /** @var DungeonStart|null $clone */
            $clone = DungeonStart::query()
                ->where('mapping_version_id', $newMappingVersion->id)
                ->where('lat', self::SENTINEL_LAT)
                ->first();

            $this->assertNotNull($clone, 'The dungeon start must have been cloned into the new MappingVersion.');
            $this->assertNotSame($dungeonStart->id, $clone->id, 'The clone must be a new row.');
            $this->assertSame($floorId, $clone->floor_id);
            $this->assertSame($targetDungeonId, $clone->target_dungeon_id);
            $this->assertSame('mapping.start.east', $clone->comment);
        } finally {
            $newMappingVersion?->delete();
            $dungeonStart->delete();
        }
    }

    #[Test]
    public function delete_givenMappingVersionWithDungeonStart_deletesItsDungeonStarts(): void
    {
        // Arrange
        $existingMappingVersion = $this->getMappingVersionThatWillBeCloned();
        $newMappingVersion      = $this->createNextMappingVersion($existingMappingVersion);

        try {
            $this->assertTrue($newMappingVersion->dungeonStarts()->exists(), 'Precondition: the new MappingVersion has cloned dungeon starts.');

            // Act
            $newMappingVersion->delete();

            // Assert
            $this->assertFalse(DungeonStart::query()->where('mapping_version_id', $newMappingVersion->id)->exists());
        } finally {
            DungeonStart::query()->where('mapping_version_id', $newMappingVersion->id)->delete();
        }
    }

    #[Test]
    public function mapContextMapIcons_givenLegacyDungeonStartMapIcon_leavesItOut(): void
    {
        // Arrange
        $mappingVersion = $this->getMappingVersionThatWillBeCloned();
        $floorId        = $mappingVersion->dungeon->floors->first()->id;
        $legacyStart    = MapIcon::create([
            'mapping_version_id' => $mappingVersion->id,
            'floor_id'           => $floorId,
            'map_icon_type_id'   => MapIconType::ALL[MapIconType::MAP_ICON_TYPE_DUNGEON_START],
            'lat'                => self::SENTINEL_LAT,
            'lng'                => 100.0,
        ]);
        $graveyard = MapIcon::create([
            'mapping_version_id' => $mappingVersion->id,
            'floor_id'           => $floorId,
            'map_icon_type_id'   => MapIconType::ALL[MapIconType::MAP_ICON_TYPE_GRAVEYARD],
            'lat'                => self::SENTINEL_LAT,
            'lng'                => 110.0,
        ]);

        try {
            // Act
            $mapIconIds = $mappingVersion->mapContextMapIcons(app(CoordinatesServiceInterface::class), false)->pluck('id');

            // Assert
            $this->assertNotContains($legacyStart->id, $mapIconIds);
            $this->assertContains($graveyard->id, $mapIconIds);
        } finally {
            $legacyStart->delete();
            $graveyard->delete();
        }
    }

    private function getMappingVersionThatWillBeCloned(): MappingVersion
    {
        /** @var Dungeon|null $dungeon */
        $dungeon = Dungeon::whereNotNull('challenge_mode_id')
            ->get()
            ->first(static function (Dungeon $dungeon): bool {
                /** @var MappingVersion|null $mappingVersion */
                $mappingVersion = $dungeon->mappingVersions()->first();

                return $mappingVersion !== null && $mappingVersion->dungeonStarts()->exists();
            });

        if ($dungeon === null) {
            $this->fail('No dungeon with dungeon starts found for testing.');
        }

        /** @var MappingVersion $mappingVersion */
        $mappingVersion = $dungeon->mappingVersions()->first();

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
