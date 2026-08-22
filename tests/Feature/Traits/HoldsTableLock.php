<?php

namespace Tests\Feature\Traits;

use Closure;
use Illuminate\Support\Facades\DB;

/**
 * Reproduces a real MySQL 1205 "Lock wait timeout exceeded" against the live test connection, so
 * that `DB::transaction($callback, $attempts)`'s retry can be tested for what it actually does
 * rather than for the fact that the call is present (#4239, #4250).
 *
 * A second PHP process takes an explicit `LOCK TABLES <table> WRITE`, which blocks any other
 * session's write to that table until it is released. The lock mechanism differs from production's
 * row/auto-increment lock contention, but MySQL raises the identical
 * "SQLSTATE[HY000]: 1205 Lock wait timeout exceeded; try restarting transaction" message either
 * way, which is all `ConcurrencyErrorDetector::causedByConcurrencyError()` matches on - so this is
 * a faithful test of the retry behaviour itself, not of the exact contention mechanism.
 */
trait HoldsTableLock
{
    /**
     * Runs $callback while $table is held under an exclusive write lock by another process, with
     * this connection's `lock_wait_timeout` lowered to 1 second. The callback's first write to
     * $table therefore genuinely times out, and only succeeds once the lock releases - which is
     * what makes it a test of the retry.
     *
     * Pick a table the code under test writes to *after* at least one other write, so a passing
     * test also proves the rollback undid those earlier writes before the retry re-ran them.
     *
     * @param int $holdMs how long the lock is held, in ms; must exceed the 1s lock_wait_timeout
     */
    protected function runWhileTableIsWriteLocked(string $table, Closure $callback, int $holdMs = 1200): void
    {
        $lockerProcess = null;
        $lockerPipes   = [];
        $lockerScript  = storage_path(sprintf('framework/testing/table_locker_%s.php', uniqid()));

        try {
            file_put_contents($lockerScript, $this->lockerScriptContents());

            $connection = config('database.connections.' . config('database.default'));

            $lockerProcess = proc_open(
                [
                    'php',
                    $lockerScript,
                    $connection['host'],
                    (string)$connection['port'],
                    $connection['database'],
                    $connection['username'],
                    (string)$connection['password'],
                    $table,
                    (string)$holdMs,
                ],
                [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
                $lockerPipes,
            );
            $this->assertIsResource($lockerProcess);

            // Block until the lock is actually held, so the callback below can never race ahead of it
            $this->assertEquals('LOCKED', trim((string)fgets($lockerPipes[1])));

            DB::statement('SET SESSION lock_wait_timeout = 1');

            $callback();
        } finally {
            if ($lockerProcess !== null) {
                // Drain the locker's "DONE" so it can exit cleanly
                fgets($lockerPipes[1]);
                proc_close($lockerProcess);
            }

            DB::statement('SET SESSION lock_wait_timeout = DEFAULT');

            if (file_exists($lockerScript)) {
                unlink($lockerScript);
            }
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
