<?php

namespace Tests\Feature\App\Models\DungeonRoute;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\MapIcon;
use App\Models\MapIconType;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('DungeonRoute')]
final class DungeonRouteGetDungeonStartMapIconTest extends PublicTestCase
{
    private const int DUNGEON_START_TYPE_ID = 10;

    private function createDungeonStartMapIcon(int $mappingVersionId, int $floorId, string $comment): MapIcon
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
    public function getDungeonStartMapIcon_givenChosenStart_returnsThatMapIcon(): void
    {
        // Arrange — an explicitly chosen start of the route's own mapping version is returned as-is
        $route   = DungeonRoute::factory()->create();
        $floorId = $route->dungeon->floors->first()->id;
        $chosen  = $this->createDungeonStartMapIcon($route->mapping_version_id, $floorId, 'mapping.start.east');
        $route->update(['dungeon_start_map_icon_id' => $chosen->id]);

        try {
            // Act
            $result = $route->fresh()->getDungeonStartMapIcon();

            // Assert
            $this->assertNotNull($result);
            $this->assertEquals($chosen->id, $result->id);
        } finally {
            $chosen->delete();
            $route->delete();
        }
    }

    #[Test]
    public function getDungeonStartMapIcon_givenNoChosenStart_returnsAStartOfTheMappingVersion(): void
    {
        // Arrange
        $route   = DungeonRoute::factory()->create(['dungeon_start_map_icon_id' => null]);
        $floorId = $route->dungeon->floors->first()->id;
        $start   = $this->createDungeonStartMapIcon($route->mapping_version_id, $floorId, 'mapping.start.east');

        try {
            // Act
            $result = $route->getDungeonStartMapIcon();

            // Assert
            $this->assertNotNull($result);
            $this->assertEquals(self::DUNGEON_START_TYPE_ID, $result->map_icon_type_id);
            $this->assertEquals($route->mapping_version_id, $result->mapping_version_id);
        } finally {
            $start->delete();
            $route->delete();
        }
    }

    #[Test]
    public function getDungeonStartMapIcon_givenNoStartsForMappingVersion_returnsNull(): void
    {
        // Arrange — point the route at a mapping version that has no map icons at all
        $route = DungeonRoute::factory()->create(['dungeon_start_map_icon_id' => null]);
        $route->update(['mapping_version_id' => $route->mapping_version_id + 999999]);

        try {
            // Act
            $result = $route->fresh()->getDungeonStartMapIcon();

            // Assert
            $this->assertNull($result);
        } finally {
            $route->delete();
        }
    }
}
