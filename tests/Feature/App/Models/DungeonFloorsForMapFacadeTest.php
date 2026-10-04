<?php

namespace Tests\Feature\App\Models;

use App\Models\Floor\Floor;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Traits\ProvidesDungeon;
use Tests\TestCases\PublicTestCase;

#[Group('Dungeon')]
#[Group('Floor')]
final class DungeonFloorsForMapFacadeTest extends PublicTestCase
{
    use ProvidesDungeon;

    #[Test]
    public function floorsForMapFacade_givenFacadeNavigationRequestedAndEnabled_returnsAllFloors(): void
    {
        // Arrange
        [$dungeon, $mappingVersion] = $this->findDungeon(facadeEnabled: true, facadeNavigation: false);
        /** @var Floor $facadeFloor */
        $facadeFloor = $dungeon->floors()->where('facade', 1)->firstOrFail();

        try {
            $facadeFloor->update(['facade_navigation' => 1]);
            $mappingVersion->unsetRelation('dungeon');

            // Act
            $floorIds = $dungeon->floorsForMapFacade($mappingVersion, true, true)->pluck('id')->sort()->values()->all();

            // Assert
            $this->assertSame($dungeon->floors()->pluck('id')->sort()->values()->all(), $floorIds);
        } finally {
            $facadeFloor->update(['facade_navigation' => 0]);
        }
    }

    #[Test]
    public function floorsForMapFacade_givenFacadeNavigationEnabledButNotRequested_returnsOnlyFacadeFloor(): void
    {
        // Arrange - thumbnails and MDT exports call this without asking for facade navigation
        [$dungeon, $mappingVersion] = $this->findDungeon(facadeEnabled: true, facadeNavigation: false);
        /** @var Floor $facadeFloor */
        $facadeFloor = $dungeon->floors()->where('facade', 1)->firstOrFail();

        try {
            $facadeFloor->update(['facade_navigation' => 1]);
            $mappingVersion->unsetRelation('dungeon');

            // Act
            $floorIds = $dungeon->floorsForMapFacade($mappingVersion, true)->pluck('id')->all();

            // Assert
            $this->assertSame([$facadeFloor->id], $floorIds);
        } finally {
            $facadeFloor->update(['facade_navigation' => 0]);
        }
    }

    #[Test]
    public function floorsForMapFacade_givenFacadeNavigationRequestedButDisabled_returnsOnlyFacadeFloor(): void
    {
        // Arrange
        [$dungeon, $mappingVersion] = $this->findDungeon(facadeEnabled: true, facadeNavigation: false);
        /** @var Floor $facadeFloor */
        $facadeFloor = $dungeon->floors()->where('facade', 1)->firstOrFail();

        // Act
        $floorIds = $dungeon->floorsForMapFacade($mappingVersion, true, true)->pluck('id')->all();

        // Assert
        $this->assertSame([$facadeFloor->id], $floorIds);
    }

    #[Test]
    public function floorsForMapFacade_givenFacadeNavigationAndSplitFloors_returnsOnlyNonFacadeFloors(): void
    {
        // Arrange
        [$dungeon, $mappingVersion] = $this->findDungeon(facadeEnabled: true, facadeNavigation: false);
        /** @var Floor $facadeFloor */
        $facadeFloor = $dungeon->floors()->where('facade', 1)->firstOrFail();

        try {
            $facadeFloor->update(['facade_navigation' => 1]);
            $mappingVersion->unsetRelation('dungeon');

            // Act
            $floors = $dungeon->floorsForMapFacade($mappingVersion, false, true)->get();

            // Assert
            $this->assertNotEmpty($floors);
            $this->assertFalse($floors->contains('id', $facadeFloor->id));
        } finally {
            $facadeFloor->update(['facade_navigation' => 0]);
        }
    }
}
