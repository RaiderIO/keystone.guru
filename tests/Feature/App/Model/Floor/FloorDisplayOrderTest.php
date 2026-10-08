<?php

namespace Tests\Feature\App\Model\Floor;

use App\Models\Dungeon;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Floor\Floor;
use App\Models\Mapping\MappingVersion;
use App\Models\User;
use App\Service\MapContext\MapContextServiceInterface;
use Database\Factories\FloorFactory;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\Traits\CreatesDungeon;
use Tests\TestCases\PublicTestCase;

#[Group('Floor')]
#[Group('MapContext')]
final class FloorDisplayOrderTest extends PublicTestCase
{
    use CreatesDungeon;

    #[Test]
    public function displayOrdered_givenFloorsRelation_ordersByDisplayOrderThenIndex(): void
    {
        // Arrange
        [$dungeon, $expectedIndices] = $this->createDungeonWithDisplayOrder();

        // Act
        $indices = $dungeon->floors()->displayOrdered()->pluck('index')->all();

        // Assert
        $this->assertSame($expectedIndices, $indices);
    }

    #[Test]
    public function displayOrdered_givenFloorsFetchedByKey_breaksTiesOnIndex(): void
    {
        // Arrange
        [$dungeon, $expectedIndices] = $this->createDungeonWithDisplayOrder();
        $floorIds                    = Floor::query()->where('dungeon_id', $dungeon->id)->pluck('id')->all();

        // Act
        $indices = Floor::query()->whereKey($floorIds)->displayOrdered()->pluck('index')->all();

        // Assert
        $this->assertSame($expectedIndices, $indices);
    }

    #[Test]
    public function getVisibleFloors_givenExploreContext_returnsFloorsInDisplayOrder(): void
    {
        // Arrange
        [$dungeon, $expectedIndices] = $this->createDungeonWithDisplayOrder();
        $mappingVersion              = $this->getMappingVersion($dungeon);

        // Act
        $visibleFloors = app(MapContextServiceInterface::class)
            ->createMapContextDungeonExplore($dungeon, $mappingVersion, User::MAP_FACADE_STYLE_SPLIT_FLOORS)
            ->getVisibleFloors();

        // Assert
        $this->assertSame($expectedIndices, array_column($visibleFloors, 'index'));
    }

    #[Test]
    public function getVisibleFloors_givenDungeonRouteContext_returnsFloorsInDisplayOrder(): void
    {
        // Arrange
        [$dungeon, $expectedIndices] = $this->createDungeonWithDisplayOrder();
        $dungeonRoute                = DungeonRoute::factory()->make([
            'dungeon_id'         => $dungeon->id,
            'mapping_version_id' => $this->getMappingVersion($dungeon)->id,
        ]);

        // Act
        $visibleFloors = app(MapContextServiceInterface::class)
            ->createMapContextDungeonRoute($dungeonRoute, User::MAP_FACADE_STYLE_SPLIT_FLOORS)
            ->getVisibleFloors();

        // Assert
        $this->assertSame($expectedIndices, array_column($visibleFloors, 'index'));
    }

    #[Test]
    public function getVisibleFloors_givenMappingVersionEditContext_returnsFloorsInDisplayOrder(): void
    {
        // Arrange
        [$dungeon, $expectedIndices] = $this->createDungeonWithDisplayOrder();
        $mappingVersion              = $this->getMappingVersion($dungeon);

        // Act
        $visibleFloors = app(MapContextServiceInterface::class)
            ->createMapContextMappingVersionEdit($dungeon->fresh(), $mappingVersion)
            ->getVisibleFloors();

        // Assert
        $this->assertSame($expectedIndices, array_column($visibleFloors, 'index'));
        $this->assertSame(range(0, count($expectedIndices) - 1), array_keys($visibleFloors));
    }

    /**
     * Index order is 1, 2, 3, 4; display order puts index 1 (display_order 0) first, then the two display_order 1
     * floors in index order, then index 2 (display_order 3). Index 4 is inserted before index 3, so the index tie-break
     * is what puts 3 first.
     *
     * @return array{Dungeon, list<int>}
     */
    private function createDungeonWithDisplayOrder(): array
    {
        $dungeon = $this->createDungeon();
        foreach ([2 => 3, 4 => 1, 3 => 1] as $index => $displayOrder) {
            FloorFactory::new()->create([
                'dungeon_id'    => $dungeon->id,
                'index'         => $index,
                'display_order' => $displayOrder,
                'name'          => sprintf('Test Floor %d', $index),
                'default'       => false,
                'active'        => true,
            ]);
        }

        Floor::query()->where('dungeon_id', $dungeon->id)->update(['active' => true]);

        return [$dungeon, [1, 3, 4, 2]];
    }

    private function getMappingVersion(Dungeon $dungeon): MappingVersion
    {
        return MappingVersion::query()->where('dungeon_id', $dungeon->id)->firstOrFail();
    }
}
