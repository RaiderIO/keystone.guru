<?php

namespace Tests\Feature\App\Service\MDT;

use App\Models\Arrow;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Polyline;
use App\Service\MDT\Import\ObjectImporter;
use App\Service\MDT\Models\ImportStringObjects;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;

/**
 * Covers #4239 - concurrent MDT imports bulk-inserting into the shared `polylines` table can hit
 * a MySQL lock wait timeout. ObjectImporter::applyObjectsToDungeonRoute() must retry the whole
 * write inside a Laravel transaction so a transient lock wait timeout is retried automatically
 * instead of failing the import outright.
 */
#[Group('MDTImportStringService')]
#[Group('MDTImportStringServiceObjects')]
class ObjectImporterTransactionRetryTest extends MDTImportStringServiceTestBase
{
    #[Test]
    public function applyObjectsToDungeonRoute_always_wrapsWritesInARetriedTransaction(): void
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

            // The whole write must be wrapped in a transaction retried up to 3 times, so a
            // transient "Lock wait timeout exceeded" (#4239) is retried by Laravel instead of
            // failing the import outright. Intercept transaction() but call through to the real
            // callback so the underlying writes still happen against the real connection.
            DB::shouldReceive('transaction')
                ->once()
                ->withArgs(static fn($callback, $attempts) => $callback instanceof \Closure && $attempts === 3)
                ->andReturnUsing(static fn($callback) => $callback());

            // Act
            app()->make(ObjectImporter::class)->applyObjectsToDungeonRoute($importStringObjects, $dungeonRoute);

            // Assert
            /** @var DungeonRoute $dungeonRoute */
            $dungeonRoute->load('arrows');
            $this->assertEquals(1, $dungeonRoute->arrows()->count());
            $this->assertEquals(1, Polyline::where('model_class', Arrow::class)->where('model_id', $dungeonRoute->arrows()->first()->id)->count());
        } finally {
            $dungeonRoute?->delete();
        }
    }
}
