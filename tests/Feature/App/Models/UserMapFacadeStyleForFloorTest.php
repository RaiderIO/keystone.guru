<?php

namespace Tests\Feature\App\Models;

use App\Models\Floor\Floor;
use App\Models\Mapping\MappingVersion;
use App\Models\User;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Traits\ProvidesDungeon;
use Tests\TestCases\PublicTestCase;

#[Group('Floor')]
#[Group('User')]
final class UserMapFacadeStyleForFloorTest extends PublicTestCase
{
    use ProvidesDungeon;

    #[Test]
    public function getMapFacadeStyleForFloor_givenFacadeNavigationAndAFloorBehindTheFacade_returnsSplitFloors(): void
    {
        // Arrange
        [$mappingVersion, $facadeFloor, $floor] = $this->getFacadeDungeonFloors();

        try {
            $facadeFloor->update(['facade_navigation' => 1]);
            $mappingVersion->unsetRelation('dungeon');

            // Act
            $mapFacadeStyle = User::getMapFacadeStyleForFloor($mappingVersion, $floor, User::MAP_FACADE_STYLE_FACADE);

            // Assert
            $this->assertSame(User::MAP_FACADE_STYLE_SPLIT_FLOORS, $mapFacadeStyle);
        } finally {
            $facadeFloor->update(['facade_navigation' => 0]);
        }
    }

    #[Test]
    public function getMapFacadeStyleForFloor_givenFacadeNavigationAndTheFacadeFloor_returnsFacade(): void
    {
        // Arrange
        [$mappingVersion, $facadeFloor] = $this->getFacadeDungeonFloors();

        try {
            $facadeFloor->update(['facade_navigation' => 1]);
            $mappingVersion->unsetRelation('dungeon');

            // Act
            $mapFacadeStyle = User::getMapFacadeStyleForFloor($mappingVersion, $facadeFloor, User::MAP_FACADE_STYLE_FACADE);

            // Assert
            $this->assertSame(User::MAP_FACADE_STYLE_FACADE, $mapFacadeStyle);
        } finally {
            $facadeFloor->update(['facade_navigation' => 0]);
        }
    }

    #[Test]
    public function getMapFacadeStyleForFloor_givenNoFacadeNavigationAndAFloorBehindTheFacade_returnsFacade(): void
    {
        // Arrange
        [$mappingVersion, , $floor] = $this->getFacadeDungeonFloors();

        // Act
        $mapFacadeStyle = User::getMapFacadeStyleForFloor($mappingVersion, $floor, User::MAP_FACADE_STYLE_FACADE);

        // Assert
        $this->assertSame(User::MAP_FACADE_STYLE_FACADE, $mapFacadeStyle);
    }

    #[Test]
    public function getMapFacadeStyleForFloor_givenSplitFloorsStyle_returnsSplitFloors(): void
    {
        // Arrange
        [$mappingVersion, $facadeFloor] = $this->getFacadeDungeonFloors();

        // Act
        $mapFacadeStyle = User::getMapFacadeStyleForFloor($mappingVersion, $facadeFloor, User::MAP_FACADE_STYLE_SPLIT_FLOORS);

        // Assert
        $this->assertSame(User::MAP_FACADE_STYLE_SPLIT_FLOORS, $mapFacadeStyle);
    }

    #[Test]
    public function getMapFacadeStyleForFloor_givenMappingVersionWithoutFacade_returnsSplitFloors(): void
    {
        // Arrange
        [$dungeon, $mappingVersion] = $this->findDungeon(facadeEnabled: false);
        /** @var Floor $floor */
        $floor = $dungeon->floors()->firstOrFail();

        // Act
        $mapFacadeStyle = User::getMapFacadeStyleForFloor($mappingVersion, $floor, User::MAP_FACADE_STYLE_FACADE);

        // Assert
        $this->assertSame(User::MAP_FACADE_STYLE_SPLIT_FLOORS, $mapFacadeStyle);
    }

    /**
     * @return array{0: MappingVersion, 1: Floor, 2: Floor}
     */
    private function getFacadeDungeonFloors(): array
    {
        [$dungeon, $mappingVersion] = $this->findDungeon(facadeEnabled: true, facadeNavigation: false);
        /** @var Floor $facadeFloor */
        $facadeFloor = $dungeon->floors()->where('facade', 1)->firstOrFail();
        /** @var Floor $floor */
        $floor = $dungeon->floors()->where('facade', 0)->firstOrFail();

        return [$mappingVersion, $facadeFloor, $floor];
    }
}
