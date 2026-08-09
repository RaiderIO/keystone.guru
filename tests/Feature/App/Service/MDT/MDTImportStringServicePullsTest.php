<?php

namespace Tests\Feature\App\Service\MDT;

use App\Models\KillZone\KillZone;
use App\Models\Npc\NpcEnemyForces;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;

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

    #[Test]
    #[Group('MDTImportStringServicePulls')]
    public function getDungeonRoute_givenTeemingRouteAndEnemyWithoutEnemyForcesTeemingOverride_fallsBackToEnemyForces(): void
    {
        $dungeonRoute               = null;
        $importedRoute              = null;
        $npcEnemyForces             = null;
        $originalEnemyForcesTeeming = null;

        try {
            // Arrange - getSafeMdtEnemies() shuffles its results, and not every safe enemy's npc has an
            // NpcEnemyForces row (e.g. shrouded-only npcs), so pull a larger candidate pool and use the
            // first one that actually has a row to flip null on.
            $dungeonRoute = $this->getMDTCompatibleDungeonRouteWithSafeEnemies(enemyCount: 10, attributes: ['teeming' => true]);
            $safeEnemies  = $this->getSafeMdtEnemies($dungeonRoute, limit: 10);

            $enemy = null;
            foreach ($safeEnemies as $candidate) {
                $npcEnemyForces = NpcEnemyForces::query()
                    ->where('mapping_version_id', $dungeonRoute->mapping_version_id)
                    ->where('npc_id', $candidate->npc_id)
                    ->first();

                if ($npcEnemyForces !== null) {
                    $enemy = $candidate;

                    break;
                }
            }

            $this->assertNotNull($enemy, 'Expected at least one safe MDT enemy with an NpcEnemyForces row for this mapping version');

            // enemy_forces_teeming can be null for an npc that has no teeming-specific override (#3912) -
            // PullImporter must fall back to enemy_forces instead of passing null into addEnemyForces(int).
            $originalEnemyForcesTeeming = $npcEnemyForces->enemy_forces_teeming;
            $npcEnemyForces->update(['enemy_forces_teeming' => null]);

            KillZone::factory()->withEnemies($enemy)->create([
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
            $this->assertCount(1, $importedRoute->killZones->first()->killZoneEnemies);
        } finally {
            $npcEnemyForces?->update(['enemy_forces_teeming' => $originalEnemyForcesTeeming]);
            $importedRoute?->delete();
            $dungeonRoute?->delete();
        }
    }
}
