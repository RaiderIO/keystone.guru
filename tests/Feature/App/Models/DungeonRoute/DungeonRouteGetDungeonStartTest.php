<?php

namespace Tests\Feature\App\Models\DungeonRoute;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonStart;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('DungeonRoute')]
final class DungeonRouteGetDungeonStartTest extends PublicTestCase
{
    private function createDungeonStart(int $mappingVersionId, int $floorId, string $comment): DungeonStart
    {
        return DungeonStart::factory()->create([
            'mapping_version_id' => $mappingVersionId,
            'floor_id'           => $floorId,
            'lat'                => -100.0,
            'lng'                => 100.0,
            'comment'            => $comment,
        ]);
    }

    #[Test]
    public function getDungeonStart_givenChosenStart_returnsThatStart(): void
    {
        // Arrange — an explicitly chosen start of the route's own mapping version is returned as-is, even when
        // the mapping version has a start with a lower id that the fallback would pick instead
        $route   = DungeonRoute::factory()->create();
        $floorId = $route->dungeon->floors->first()->id;
        $other   = $this->createDungeonStart($route->mapping_version_id, $floorId, 'mapping.start.west');
        $chosen  = $this->createDungeonStart($route->mapping_version_id, $floorId, 'mapping.start.east');
        $route->update(['dungeon_start_id' => $chosen->id]);

        try {
            // Act
            $result = $route->fresh()->getDungeonStart();

            // Assert
            $this->assertNotNull($result);
            $this->assertEquals($chosen->id, $result->id);
        } finally {
            $chosen->delete();
            $other->delete();
            $route->delete();
        }
    }

    #[Test]
    public function getDungeonStart_givenNoChosenStart_returnsAStartOfTheMappingVersion(): void
    {
        // Arrange
        $route   = DungeonRoute::factory()->create(['dungeon_start_id' => null]);
        $floorId = $route->dungeon->floors->first()->id;
        $start   = $this->createDungeonStart($route->mapping_version_id, $floorId, 'mapping.start.east');

        try {
            // Act
            $result = $route->getDungeonStart();

            // Assert
            $this->assertInstanceOf(DungeonStart::class, $result);
            $this->assertEquals($route->mapping_version_id, $result->mapping_version_id);
        } finally {
            $start->delete();
            $route->delete();
        }
    }

    #[Test]
    public function getDungeonStart_givenStart_returnsItWithOnlyItsFloorLoaded(): void
    {
        // Arrange
        $route   = DungeonRoute::factory()->create(['dungeon_start_id' => null]);
        $floorId = $route->dungeon->floors->first()->id;
        $start   = $this->createDungeonStart($route->mapping_version_id, $floorId, 'mapping.start.east');

        try {
            // Act
            $result = $route->getDungeonStart();

            // Assert
            $this->assertNotNull($result);
            $this->assertTrue($result->relationLoaded('floor'));
            $this->assertFalse($result->relationLoaded('mappingVersion'));
            $this->assertFalse($result->relationLoaded('targetDungeon'));
        } finally {
            $start->delete();
            $route->delete();
        }
    }

    #[Test]
    public function getDungeonStart_givenNoStartsForMappingVersion_returnsNull(): void
    {
        // Arrange — point the route at a mapping version that has no dungeon starts at all
        $route = DungeonRoute::factory()->create(['dungeon_start_id' => null]);
        $route->update(['mapping_version_id' => $route->mapping_version_id + 999999]);

        try {
            // Act
            $result = $route->fresh()->getDungeonStart();

            // Assert
            $this->assertNull($result);
        } finally {
            $route->delete();
        }
    }
}
