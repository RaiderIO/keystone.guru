<?php

namespace Tests\Feature\App\Service\MDT;

use App\Models\Dungeon;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Path;
use App\Service\MDT\Import\RiftOffsetImporter;
use App\Service\MDT\Models\ImportStringRiftOffsets;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Traits\HoldsTableLock;
use Tests\TestCases\PublicTestCase;

/**
 * Covers #4250 - the same unprotected bulk-insert-into-`polylines` shape that #4239 fixed for
 * ObjectImporter also existed here, so a concurrent import could fail this one outright with a
 * MySQL 1205 "Lock wait timeout exceeded".
 *
 * How the real contention is reproduced is documented on {@see HoldsTableLock}.
 */
#[Group('MDT')]
#[Group('RiftOffsetImporter')]
final class RiftOffsetImporterTransactionRetryTest extends PublicTestCase
{
    use HoldsTableLock;

    private const string DUNGEON_KEY_UNPACKED_ONLY = 'theunderrot';
    private const int    BRUTAL_NPC_ID             = 161124;

    #[Test]
    public function applyRiftOffsetsToDungeonRoute_givenATransientPolylinesLockWaitTimeout_retriesAndSucceeds(): void
    {
        $dungeonRoute = null;

        try {
            // Arrange
            $dungeon        = Dungeon::where('key', self::DUNGEON_KEY_UNPACKED_ONLY)->firstOrFail();
            $mappingVersion = $dungeon->getCurrentMappingVersion();

            /** @var RiftOffsetImporter $importer */
            $importer = app(RiftOffsetImporter::class);

            $importStringRiftOffsets = $importer->parseRiftOffsets(new ImportStringRiftOffsets(
                warnings:       new Collection(),
                dungeon:        $dungeon,
                mappingVersion: $mappingVersion,
                seasonalIndex:  null,
                riftOffsets:    [
                    1 => [
                        self::BRUTAL_NPC_ID => ['x' => 50.0, 'y' => 50.0],
                    ],
                ],
                week: 1,
            ));

            // Pin the premise: the parse half produced exactly the one offset the apply half needs
            $this->assertCount(
                1,
                $importStringRiftOffsets->getPaths(),
                'Test premise no longer holds: parseRiftOffsets() produced no path to apply',
            );

            $dungeonRoute = DungeonRoute::factory()->create([
                'dungeon_id'         => $dungeon->id,
                'mapping_version_id' => $mappingVersion->id,
            ]);

            // Act - `polylines` is the fourth table this method writes, so the first attempt gets
            // as far as inserting the map icon and the path before it times out and is rolled back
            $this->runWhileTableIsWriteLocked(
                'polylines',
                static fn() => $importer->applyRiftOffsetsToDungeonRoute($importStringRiftOffsets, $dungeonRoute),
            );

            // Assert - the import completed despite the transient lock. Exactly one map icon and
            // one path is the load-bearing part: the retry re-runs the whole body, and it re-reads
            // its own paths back through a `polyline_id = -1` filter, so anything the first attempt
            // left behind would both duplicate rows here and misalign the polyline matching.
            $this->assertEquals(1, $dungeonRoute->mapicons()->count());
            $this->assertEquals(1, $dungeonRoute->paths()->count());

            /** @var Path $path */
            $path = $dungeonRoute->paths()->first();
            $this->assertNotEquals(-1, $path->polyline_id, 'The path was never linked back to its polyline');
        } finally {
            /** @var DungeonRoute|null $dungeonRoute */
            $dungeonRoute?->delete();
        }
    }
}
