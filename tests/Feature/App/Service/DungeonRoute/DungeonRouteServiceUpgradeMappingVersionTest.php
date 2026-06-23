<?php

namespace Tests\Feature\App\Service\DungeonRoute;

use App\Models\Dungeon;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\MapIcon;
use App\Models\MapIconType;
use App\Models\Mapping\MappingVersion;
use App\Service\DungeonRoute\DungeonRouteServiceInterface;
use Illuminate\Database\Eloquent\Builder;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Traits\ProvidesDungeon;
use Tests\TestCases\PublicTestCase;

#[Group('DungeonRouteService')]
final class DungeonRouteServiceUpgradeMappingVersionTest extends PublicTestCase
{
    use ProvidesDungeon;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        config(['broadcasting.default' => 'null']);
    }

    /**
     * Creates a newer, isolated mapping version for the given dungeon so that getCurrentMappingVersion()
     * resolves to it (it gets the highest version number), making the dungeon's seeded mapping version "old".
     */
    private function createNewerMappingVersion(Dungeon $dungeon, MappingVersion $existing): MappingVersion
    {
        return MappingVersion::create([
            'game_version_id'                 => $existing->game_version_id,
            'dungeon_id'                      => $dungeon->id,
            'version'                         => $existing->version + 1000,
            'enemy_forces_required'           => $existing->enemy_forces_required,
            'enemy_forces_required_teeming'   => $existing->enemy_forces_required_teeming,
            'enemy_forces_shrouded'           => $existing->enemy_forces_shrouded,
            'enemy_forces_shrouded_zul_gamux' => $existing->enemy_forces_shrouded_zul_gamux,
            'timer_max_seconds'               => $existing->timer_max_seconds,
            'facade_enabled'                  => false,
        ]);
    }

    private function createDungeonStartMapIcon(int $mappingVersionId, int $floorId, ?string $comment): MapIcon
    {
        return MapIcon::create([
            'mapping_version_id' => $mappingVersionId,
            'floor_id'           => $floorId,
            'dungeon_route_id'   => null,
            'team_id'            => null,
            'map_icon_type_id'   => MapIconType::ALL[MapIconType::MAP_ICON_TYPE_DUNGEON_START],
            'lat'                => -100.0,
            'lng'                => 100.0,
            'comment'            => $comment,
            'permanent_tooltip'  => false,
            'seasonal_index'     => 0,
        ]);
    }

    #[Test]
    public function upgradeMappingVersion_givenMatchingComment_remapsDungeonStartMapIconId(): void
    {
        // Arrange
        $dungeon    = $this->getDungeonWithNonFacadeFloor(fn(Builder $query) => $query->whereNotNull('challenge_mode_id'));
        $existingMV = $dungeon->getCurrentMappingVersion();
        $newMV      = $this->createNewerMappingVersion($dungeon, $existingMV);
        $floorId    = $dungeon->floors()->where('facade', false)->value('id');

        $route    = DungeonRoute::factory()->create(['dungeon_id' => $dungeon->id, 'mapping_version_id' => $existingMV->id]);
        $oldStart = $this->createDungeonStartMapIcon($existingMV->id, $floorId, 'mapping.start.east');
        $newStart = $this->createDungeonStartMapIcon($newMV->id, $floorId, 'mapping.start.east');
        $route->update(['dungeon_start_map_icon_id' => $oldStart->id]);

        try {
            // Act
            app(DungeonRouteServiceInterface::class)->upgradeMappingVersion($route);

            // Assert
            $fresh = $route->fresh();
            $this->assertEquals($newMV->id, $fresh->mapping_version_id);
            $this->assertEquals($newStart->id, $fresh->dungeon_start_map_icon_id);
        } finally {
            $route->delete();
            $newStart->delete();
            $oldStart->delete();
            $newMV->delete();
        }
    }

    #[Test]
    public function upgradeMappingVersion_givenNoMatchingComment_setsDungeonStartMapIconIdToNull(): void
    {
        // Arrange
        $dungeon    = $this->getDungeonWithNonFacadeFloor(fn(Builder $query) => $query->whereNotNull('challenge_mode_id'));
        $existingMV = $dungeon->getCurrentMappingVersion();
        $newMV      = $this->createNewerMappingVersion($dungeon, $existingMV);
        $floorId    = $dungeon->floors()->where('facade', false)->value('id');

        $route    = DungeonRoute::factory()->create(['dungeon_id' => $dungeon->id, 'mapping_version_id' => $existingMV->id]);
        $oldStart = $this->createDungeonStartMapIcon($existingMV->id, $floorId, 'mapping.start.east');
        $newStart = $this->createDungeonStartMapIcon($newMV->id, $floorId, 'mapping.start.west');
        $route->update(['dungeon_start_map_icon_id' => $oldStart->id]);

        try {
            // Act
            app(DungeonRouteServiceInterface::class)->upgradeMappingVersion($route);

            // Assert
            $fresh = $route->fresh();
            $this->assertEquals($newMV->id, $fresh->mapping_version_id);
            $this->assertNull($fresh->dungeon_start_map_icon_id);
        } finally {
            $route->delete();
            $newStart->delete();
            $oldStart->delete();
            $newMV->delete();
        }
    }

    #[Test]
    public function upgradeMappingVersion_givenNoChosenStart_keepsDungeonStartMapIconIdNull(): void
    {
        // Arrange
        $dungeon    = $this->getDungeonWithNonFacadeFloor(fn(Builder $query) => $query->whereNotNull('challenge_mode_id'));
        $existingMV = $dungeon->getCurrentMappingVersion();
        $newMV      = $this->createNewerMappingVersion($dungeon, $existingMV);

        $route = DungeonRoute::factory()->create([
            'dungeon_id'                => $dungeon->id,
            'mapping_version_id'        => $existingMV->id,
            'dungeon_start_map_icon_id' => null,
        ]);

        try {
            // Act
            app(DungeonRouteServiceInterface::class)->upgradeMappingVersion($route);

            // Assert
            $fresh = $route->fresh();
            $this->assertEquals($newMV->id, $fresh->mapping_version_id);
            $this->assertNull($fresh->dungeon_start_map_icon_id);
        } finally {
            $route->delete();
            $newMV->delete();
        }
    }
}
