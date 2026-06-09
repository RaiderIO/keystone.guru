<?php

namespace Tests\Feature\App\Service\MDT;

use App\Models\MapIcon;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;

#[Group('UsesLua')]
#[Group('MDTImportStringService')]
#[Group('MDTImportStringServiceObjects')]
class MDTImportStringServiceObjectsTest extends MDTImportStringServiceTestBase
{
    #[Test]
    public function getDungeonRoute_givenRouteWithMapIcon_returnsOneMapIcon(): void
    {
        $dungeonRoute  = null;
        $importedRoute = null;

        try {
            // Arrange
            $dungeonRoute = $this->getMDTCompatibleNonFacadeDungeonRoute();
            $floor        = $dungeonRoute->dungeon->floors()->first();

            MapIcon::factory()->create([
                'dungeon_route_id' => $dungeonRoute->id,
                'floor_id'         => $floor->id,
            ]);

            $encodedString = $this->exportDungeonRouteToString($dungeonRoute);

            // Act
            $importedRoute = $this->importStringToDungeonRoute($encodedString);

            // Assert
            $this->assertEquals(1, $importedRoute->mapIcons()->count());
        } finally {
            $importedRoute?->delete();
            $dungeonRoute?->delete();
        }
    }

    #[Test]
    public function getDungeonRoute_givenRouteWithArrow_returnsOneArrow(): void
    {
        $dungeonRoute  = null;
        $importedRoute = null;

        try {
            // Arrange
            $dungeonRoute = $this->getMDTCompatibleNonFacadeDungeonRoute();
            $this->createArrowForRoute($dungeonRoute);

            $encodedString = $this->exportDungeonRouteToString($dungeonRoute);

            // Act
            $importedRoute = $this->importStringToDungeonRoute($encodedString);

            // Assert — imported as Arrow, not as Brushlines
            $this->assertEquals(1, $importedRoute->arrows()->count());
            $this->assertEquals(0, $importedRoute->brushlines()->count());
        } finally {
            $importedRoute?->delete();
            $dungeonRoute?->delete();
        }
    }

    #[Test]
    public function getDungeonRoute_givenRouteWithBrushline_returnsOneBrushline(): void
    {
        $dungeonRoute  = null;
        $importedRoute = null;

        try {
            // Arrange
            $dungeonRoute = $this->getMDTCompatibleNonFacadeDungeonRoute();
            $this->createBrushlineForRoute($dungeonRoute);

            $encodedString = $this->exportDungeonRouteToString($dungeonRoute);

            // Act
            $importedRoute = $this->importStringToDungeonRoute($encodedString);

            // Assert
            $this->assertEquals(1, $importedRoute->brushlines()->count());
        } finally {
            $importedRoute?->delete();
            $dungeonRoute?->delete();
        }
    }
}
