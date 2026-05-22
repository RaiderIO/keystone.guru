<?php

namespace Tests\Feature\App\Service\MDT;

use App\Models\MapIcon;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;

#[Group('UsesLua')]
#[Group('MDTImportStringService')]
class MDTImportStringServiceObjectsTest extends MDTImportStringServiceTestBase
{
    #[Test]
    #[Group('MDTImportStringServiceObjects')]
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
    #[Group('MDTImportStringServiceObjects')]
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
