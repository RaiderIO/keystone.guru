<?php

namespace Tests\Feature\App\Service\MDT;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Enemy;
use App\Models\RaidMarker;
use App\Service\MDT\Import\RaidMarkerImporter;
use App\Service\MDT\Models\ImportStringRaidMarkers;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Traits\GeneratesDungeonRoutes;
use Tests\Feature\Traits\HoldsTableLock;
use Tests\TestCases\PublicTestCase;

/**
 * Covers #4250. This method's single bulk insert is already atomic, so what the retried transaction
 * buys here is purely the retry: without it, one MySQL 1205 "Lock wait timeout exceeded" on a table
 * every concurrent import writes to fails the whole import, exactly as in #4239.
 *
 * How the real contention is reproduced is documented on {@see HoldsTableLock}.
 */
#[Group('MDT')]
#[Group('RaidMarkerImporter')]
final class RaidMarkerImporterTransactionRetryTest extends PublicTestCase
{
    use GeneratesDungeonRoutes;
    use HoldsTableLock;

    #[Test]
    public function applyRaidMarkersToDungeonRoute_givenATransientRaidMarkerLockWaitTimeout_retriesAndSucceeds(): void
    {
        $dungeonRoute = null;

        try {
            // Arrange
            $dungeonRoute = $this->createNonFacadeDungeonRouteWithEnemies();

            /** @var Enemy $enemy */
            $enemy = $dungeonRoute->mappingVersion->enemies()->firstOrFail();
            /** @var RaidMarker $raidMarker */
            $raidMarker = RaidMarker::query()->firstOrFail();

            $importStringRaidMarkers = new ImportStringRaidMarkers(
                warnings:            new Collection(),
                dungeon:             $dungeonRoute->dungeon,
                mappingVersion:      $dungeonRoute->mappingVersion,
                mdtEnemyAssignments: [],
            );
            $importStringRaidMarkers->addRaidMarkerAttributes([
                'npc_id'         => $enemy->npc_id,
                'mdt_id'         => $enemy->mdt_id,
                'enemy_id'       => $enemy->id,
                'raid_marker_id' => $raidMarker->id,
            ]);

            // Act
            $this->runWhileTableIsWriteLocked(
                'dungeon_route_enemy_raid_markers',
                static fn() => app()->make(RaidMarkerImporter::class)
                    ->applyRaidMarkersToDungeonRoute($importStringRaidMarkers, $dungeonRoute),
            );

            // Assert - the import completed despite the transient lock, with no row duplicated by
            // the rolled-back first attempt
            $this->assertEquals(1, $dungeonRoute->enemyRaidMarkers()->count());
            $this->assertEquals($raidMarker->id, $dungeonRoute->enemyRaidMarkers()->first()->raid_marker_id);
        } finally {
            /** @var DungeonRoute|null $dungeonRoute */
            $dungeonRoute?->delete();
        }
    }
}
