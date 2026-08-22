<?php

namespace Tests\Feature\App\Service\MDT;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Enemy;
use App\Service\MDT\Import\PullImporter;
use App\Service\MDT\Models\ImportStringPulls;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Traits\GeneratesDungeonRoutes;
use Tests\Feature\Traits\HoldsTableLock;
use Tests\TestCases\PublicTestCase;

/**
 * Covers #4250 - this method bulk-inserts into the kill zone tables that every concurrent import
 * writes to, with no transaction, so a MySQL 1205 "Lock wait timeout exceeded" would fail the
 * import outright the way it did for `polylines` in #4239.
 *
 * How the real contention is reproduced is documented on {@see HoldsTableLock}.
 */
#[Group('MDT')]
#[Group('PullImporter')]
final class PullImporterTransactionRetryTest extends PublicTestCase
{
    use GeneratesDungeonRoutes;
    use HoldsTableLock;

    #[Test]
    public function applyPullsToDungeonRoute_givenATransientKillZonesLockWaitTimeout_retriesAndSucceeds(): void
    {
        $dungeonRoute = null;

        try {
            // Arrange
            $dungeonRoute = $this->createNonFacadeDungeonRouteWithEnemies();

            /** @var Enemy $enemy */
            $enemy = $dungeonRoute->mappingVersion->enemies()->firstOrFail();

            $importStringPulls = new ImportStringPulls(
                warnings:       new Collection(),
                errors:         new Collection(),
                dungeon:        $dungeonRoute->dungeon,
                mappingVersion: $dungeonRoute->mappingVersion,
                isRouteTeeming: false,
                seasonalIndex:  null,
                mdtPulls:       [],
            );
            $importStringPulls->addEnemyForces(123);
            $importStringPulls->addKillZoneAttributes([
                'index'           => 1,
                'color'           => '#ff0000',
                'description'     => null,
                'killZoneEnemies' => [
                    [
                        'npc_id'   => $enemy->npc_id,
                        'mdt_id'   => $enemy->mdt_id,
                        'enemy_id' => $enemy->id,
                        // Removed by the importer before insert - present so this fixture matches
                        // the shape parseValuePulls() actually produces
                        'enemy' => $enemy,
                    ],
                ],
                'spells' => [],
            ]);

            // Act - `kill_zones` is written after the route's own enemy_forces update, so the first
            // attempt gets as far as that update before it times out and is rolled back
            $this->runWhileTableIsWriteLocked(
                'kill_zones',
                static fn() => app()->make(PullImporter::class)
                    ->applyPullsToDungeonRoute($importStringPulls, $dungeonRoute),
            );

            // Assert - the import completed despite the transient lock, with no pull duplicated by
            // the rolled-back first attempt
            $dungeonRoute->refresh();
            $this->assertEquals(1, $dungeonRoute->killZones()->count());
            $this->assertEquals(1, $dungeonRoute->killZones()->first()->killZoneEnemies()->count());

            // The enemy_forces update happens before the lock is hit, so this also proves the
            // rolled-back attempt's write was re-applied rather than silently lost
            $this->assertEquals(123, $dungeonRoute->enemy_forces);
        } finally {
            /** @var DungeonRoute|null $dungeonRoute */
            $dungeonRoute?->delete();
        }
    }
}
