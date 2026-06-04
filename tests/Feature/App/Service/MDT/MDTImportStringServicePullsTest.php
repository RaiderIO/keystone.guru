<?php

namespace Tests\Feature\App\Service\MDT;

use App\Models\KillZone\KillZone;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Attributes\Repeat;

#[Group('UsesLua')]
#[Group('MDTImportStringService')]
class MDTImportStringServicePullsTest extends MDTImportStringServiceTestBase
{
    #[Test]
    #[Group('MDTImportStringServicePulls')]
    public function getDungeonRoute_givenRouteWithThreeKillZones_returnsThreeKillZones(): void
    {
        $dungeonRoute  = null;
        $importedRoute = null;

        try {
            // Arrange
            $dungeonRoute  = $this->getMDTCompatibleDungeonRouteWithSafeEnemies();
            $randomEnemies = $this->getSafeMdtEnemies($dungeonRoute, 3);

            foreach (range(1, 3) as $index) {
                KillZone::factory()->withEnemies($randomEnemies->get($index - 1))->create([
                    'dungeon_route_id' => $dungeonRoute->id,
                    'index'            => $index,
                    'description'      => null,
                ]);
            }

            $warnings      = collect();
            $encodedString = $this->exportDungeonRouteToString($dungeonRoute, $warnings);

            // Act
            $importedRoute = $this->importStringToDungeonRoute($encodedString);

            // Assert
            $this->assertCount(3, $importedRoute->killZones);
        } catch (\Exception $e) {
            dump(
                $dungeonRoute->dungeon->key,
                $dungeonRoute->mappingVersion->id,
                $randomEnemies->pluck('id'),
                $warnings,
            );

            throw $e;
        } finally {
            $importedRoute?->delete();
            $dungeonRoute?->delete();
        }
    }

    #[Test]
    #[Group('MDTImportStringServicePulls')]
    public function getDungeonRoute_givenKillZoneWithTwoEnemies_returnsTwoEnemiesInKillZone(): void
    {
        $dungeonRoute  = null;
        $importedRoute = null;

        try {
            // Arrange
            $dungeonRoute = $this->getMDTCompatibleDungeonRouteWithSafeEnemies(enemyCount: 2);
            $enemies      = $this->getSafeMdtEnemies($dungeonRoute, limit: 2);

            KillZone::factory()->withEnemies(...$enemies)->create([
                'dungeon_route_id' => $dungeonRoute->id,
                'index'            => 1,
                'description'      => null,
            ]);

            $encodedString = $this->exportDungeonRouteToString($dungeonRoute);

            // Act
            $importedRoute = $this->importStringToDungeonRoute($encodedString);
            $importedRoute->load(['killZones.killZoneEnemies']);

            // Assert
            $this->assertCount(1, $importedRoute->killZones);
            $this->assertCount(2, $importedRoute->killZones->first()->killZoneEnemies);
        } finally {
            $importedRoute?->delete();
            $dungeonRoute?->delete();
        }
    }
}
