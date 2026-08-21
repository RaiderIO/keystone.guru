<?php

namespace Tests\Feature\App\Service\MDT;

use App\Models\Arrow;
use App\Models\DungeonRoute\DungeonRoute;
use App\Service\MDT\Import\ObjectImporter;
use App\Service\MDT\Models\ImportStringObjects;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;

/**
 * Covers #4239 - concurrent MDT imports bulk-inserting into the shared `polylines` table were
 * hitting a real MySQL 1205 "Lock wait timeout exceeded" error and failing the import outright.
 *
 * This reproduces that exact MySQL error against a real connection - a second PHP process takes
 * an explicit `LOCK TABLES polylines WRITE`, which blocks any other session's write to the table
 * until it's released, the same way concurrent bulk inserts contended for it in production. The
 * lock mechanism differs from production's row/auto-increment lock contention, but MySQL raises
 * the identical "SQLSTATE[HY000]: 1205 Lock wait timeout exceeded; try restarting transaction"
 * message either way, which is all `DB::transaction()`'s retry matches on - so this is a faithful
 * test of the retry behaviour itself, not of the exact contention mechanism seen in production.
 */
#[Group('MDTImportStringService')]
#[Group('MDTImportStringServiceObjects')]
class ObjectImporterTransactionRetryTest extends MDTImportStringServiceTestBase
{
    #[Test]
    public function applyObjectsToDungeonRoute_givenATransientPolylinesLockWaitTimeout_retriesAndSucceeds(): void
    {
        $dungeonRoute  = null;
        $lockerProcess = null;
        $lockerPipes   = [];
        $lockerScript  = null;

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

            // Hold a real table lock on `polylines` in a separate PHP process for slightly
            // longer than this connection's lock_wait_timeout, so the first write attempt made
            // by applyObjectsToDungeonRoute() genuinely times out against real MySQL.
            $connection   = config('database.connections.' . config('database.default'));
            $lockerScript = storage_path('framework/testing/polylines_locker_' . uniqid() . '.php');
            file_put_contents($lockerScript, $this->lockerScriptContents());

            $lockerProcess = proc_open(
                [
                    'php',
                    $lockerScript,
                    $connection['host'],
                    (string)$connection['port'],
                    $connection['database'],
                    $connection['username'],
                    (string)$connection['password'],
                    'polylines',
                    '1200',
                ],
                [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
                $lockerPipes,
            );
            $this->assertIsResource($lockerProcess);
            $this->assertEquals('LOCKED', trim((string)fgets($lockerPipes[1])));

            DB::statement('SET SESSION lock_wait_timeout = 1');

            // Act - the first write attempt inside the transaction must hit the real lock wait
            // timeout above and be retried automatically
            app()->make(ObjectImporter::class)->applyObjectsToDungeonRoute($importStringObjects, $dungeonRoute);

            // Assert - the import completed despite the transient lock
            $dungeonRoute->load('arrows');
            $this->assertEquals(1, $dungeonRoute->arrows()->count());
            $this->assertNotEquals(-1, $dungeonRoute->arrows()->first()->polyline_id);
        } finally {
            if ($lockerProcess !== null) {
                fgets($lockerPipes[1] ?? null);
                proc_close($lockerProcess);
            }
            DB::statement('SET SESSION lock_wait_timeout = DEFAULT');
            if ($lockerScript !== null && file_exists($lockerScript)) {
                unlink($lockerScript);
            }
            /** @var DungeonRoute|null $dungeonRoute */
            $dungeonRoute?->delete();
        }
    }

    private function lockerScriptContents(): string
    {
        return <<<'PHP'
            <?php
            [, $host, $port, $database, $username, $password, $table, $holdMs] = $argv;
            $pdo = new PDO("mysql:host={$host};port={$port};dbname={$database}", $username, $password);
            $pdo->exec("LOCK TABLES `{$table}` WRITE");
            fwrite(STDOUT, "LOCKED\n");
            fflush(STDOUT);
            usleep((int)$holdMs * 1000);
            $pdo->exec('UNLOCK TABLES');
            fwrite(STDOUT, "DONE\n");
            PHP;
    }
}
