<?php

namespace Tests\Feature\App\Service\MDT;

use App\Models\Arrow;
use App\Models\DungeonRoute\DungeonRoute;
use App\Service\MDT\Import\ObjectImporter;
use App\Service\MDT\Models\ImportStringObjects;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Traits\HoldsTableLock;

/**
 * Covers #4239 - concurrent MDT imports bulk-inserting into the shared `polylines` table were
 * hitting a real MySQL 1205 "Lock wait timeout exceeded" error and failing the import outright.
 *
 * How the real contention is reproduced - and why locking a table is a faithful stand-in for
 * production's row-level contention - is documented on {@see HoldsTableLock}.
 */
#[Group('MDTImportStringService')]
#[Group('MDTImportStringServiceObjects')]
class ObjectImporterTransactionRetryTest extends MDTImportStringServiceTestBase
{
    use HoldsTableLock;

    #[Test]
    public function applyObjectsToDungeonRoute_givenATransientPolylinesLockWaitTimeout_retriesAndSucceeds(): void
    {
        $dungeonRoute = null;

        try {
            // Arrange
            $dungeonRoute = $this->getMDTCompatibleNonFacadeDungeonRoute();
            $floor        = $dungeonRoute->dungeon->floors()->first();

            $importStringObjects = new ImportStringObjects(
                new Collection(),
                new Collection(),
                $dungeonRoute->dungeon,
                $dungeonRoute->mappingVersion,
                new Collection(),
                [],
            );
            $importStringObjects->getArrows()->push([
                'floor_id' => $floor->id,
                'polyline' => [
                    'color'         => '#ff0000',
                    'weight'        => 2,
                    'vertices_json' => json_encode([
                        ['lat' => -100.0, 'lng' => 200.0],
                        ['lat' => -150.0, 'lng' => 250.0],
                    ]),
                    'model_class' => Arrow::class,
                ],
            ]);

            // Act - `polylines` is only written after the `arrows` insert, so the first attempt
            // gets as far as inserting an arrow before it times out and is rolled back
            $this->runWhileTableIsWriteLocked(
                'polylines',
                static fn() => app()->make(ObjectImporter::class)
                    ->applyObjectsToDungeonRoute($importStringObjects, $dungeonRoute),
            );

            // Assert - the import completed despite the transient lock, and the rolled-back first
            // attempt left no duplicate arrow behind
            $dungeonRoute->load('arrows');
            $this->assertEquals(1, $dungeonRoute->arrows()->count());
            $this->assertNotEquals(-1, $dungeonRoute->arrows()->first()->polyline_id);
        } finally {
            /** @var DungeonRoute|null $dungeonRoute */
            $dungeonRoute?->delete();
        }
    }
}
