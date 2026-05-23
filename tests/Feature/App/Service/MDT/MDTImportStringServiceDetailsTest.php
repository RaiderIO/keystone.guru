<?php

namespace Tests\Feature\App\Service\MDT;

use App\Models\KillZone\KillZone;
use App\Models\MapIcon;
use App\Service\MDT\MDTImportStringServiceInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;

#[Group('UsesLua')]
#[Group('MDTImportStringService')]
class MDTImportStringServiceDetailsTest extends MDTImportStringServiceTestBase
{
    #[Test]
    #[Group('MDTImportStringServiceDetails')]
    public function getDetails_givenRouteWithThreeKillZones_returnsThreeAsPullCount(): void
    {
        $dungeonRoute = null;

        try {
            // Arrange
            $dungeonRoute = $this->getMDTCompatibleDungeonRouteWithSafeEnemies();
            $randomEnemy  = $this->getSafeMdtEnemies($dungeonRoute)->first();

            foreach (range(1, 3) as $index) {
                KillZone::factory()->withEnemies($randomEnemy)->create([
                    'dungeon_route_id' => $dungeonRoute->id,
                    'index'            => $index,
                    'description'      => null,
                ]);
            }

            $encodedString = $this->exportDungeonRouteToString($dungeonRoute);

            // Act
            $details = app()->make(MDTImportStringServiceInterface::class)
                ->setEncodedString($encodedString)
                ->getDetails(collect(), collect());

            // Assert
            $this->assertEquals(3, $details->getPulls());
        } finally {
            $dungeonRoute?->delete();
        }
    }

    #[Test]
    #[Group('MDTImportStringServiceDetails')]
    public function getDetails_givenRouteWithMapIcon_returnsOneAsNoteCount(): void
    {
        $dungeonRoute = null;

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
            $details = app()->make(MDTImportStringServiceInterface::class)
                ->setEncodedString($encodedString)
                ->getDetails(collect(), collect());

            // Assert
            $this->assertEquals(1, $details->getNotes());
        } finally {
            $dungeonRoute?->delete();
        }
    }
}
