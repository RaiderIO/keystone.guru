<?php

namespace Tests\Feature\App\Service\DungeonRoute;

use App\Models\DungeonRoute\DungeonRoute;
use App\Service\DungeonRoute\DungeonRouteServiceInterface;
use Illuminate\Database\Eloquent\Builder;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;

#[Group('DungeonRouteService')]
final class DungeonRouteServiceUpgradeMappingVersionTest extends DungeonRouteSaveServiceTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        config(['broadcasting.default' => 'null']);
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
