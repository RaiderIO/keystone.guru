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
 *
 * Every wait in here is bounded (#4345). The original version waited forever twice over: the
 * locker child ran `LOCK TABLES` under MySQL's default lock_wait_timeout of one year, and the
 * parent blocked on timeout-less `fgets()` calls waiting for the child's markers - so any other
 * session holding a write-transaction metadata lock on the table (on a schema shared with the dev
 * stack: a Horizon thumbnail render, a cron sweep, a browser request) silently wedged the whole
 * suite. Now the child gives up after $lockAcquireTimeoutSeconds and reports the MySQL error, the
 * parent reads with deadlines, and a blocked lock fails the test loudly - naming the blocking
 * sessions - instead of hanging.
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
     * @param int $holdMs                    how long the lock is held, in ms; must exceed the 1s lock_wait_timeout
     * @param int $lockAcquireTimeoutSeconds how long the locker child may wait to acquire the lock before
     *                                       the test fails loudly instead of hanging the suite
     */
    protected function runWhileTableIsWriteLocked(
        string  $table,
        Closure $callback,
        int     $holdMs = 1200,
        int     $lockAcquireTimeoutSeconds = 10,
    ): void {
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
                    (string)$lockAcquireTimeoutSeconds,
                ],
                [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
                $lockerPipes,
            );
            $this->assertIsResource($lockerProcess);

            // Block until the lock is actually held, so the callback below can never race ahead of
            // it - but never longer than the child's own acquire timeout plus startup slack. A
            // child that hit its lock_wait_timeout reports "FAILED: <mysql error>" instead of
            // "LOCKED"; a child that reports nothing at all is dead or wedged.
            $confirmation = $this->readLineWithTimeout($lockerPipes[1], $lockAcquireTimeoutSeconds + 5);
            if ($confirmation === null) {
                $this->fail(sprintf(
                    'Table locker child produced no output within %ds while trying to LOCK TABLES `%s` WRITE - killed it instead of hanging the suite. Sessions in a lock wait: %s',
                    $lockAcquireTimeoutSeconds + 5,
                    $table,
                    $this->describeLockWaitSessions(),
                ));
            }
            if ($confirmation !== 'LOCKED') {
                $this->fail(sprintf(
                    'Table locker child could not acquire LOCK TABLES `%s` WRITE: %s. Another session holds a conflicting (metadata) lock on the table. Sessions in a lock wait: %s',
                    $table,
                    $confirmation,
                    $this->describeLockWaitSessions(),
                ));
            }

            DB::statement('SET SESSION lock_wait_timeout = 1');

            $callback();
        } finally {
            if (is_resource($lockerProcess)) {
                // Drain the locker's "DONE" so it can exit cleanly - bounded: the child releases
                // after $holdMs, so anything much beyond that means it is wedged and gets killed
                // instead of hanging the suite.
                $this->readLineWithTimeout($lockerPipes[1], (int)ceil($holdMs / 1000) + 5);
                if (proc_get_status($lockerProcess)['running']) {
                    proc_terminate($lockerProcess, 9);
                }
                proc_close($lockerProcess);
            }

            DB::statement('SET SESSION lock_wait_timeout = DEFAULT');

            if (file_exists($lockerScript)) {
                unlink($lockerScript);
            }
        }
    }

    /**
     * Reads one line from $stream, waiting at most $timeoutSeconds. Returns the trimmed line, or
     * null when the deadline passes or the stream closes without ever producing one.
     *
     * @param resource $stream
     */
    private function readLineWithTimeout(mixed $stream, int $timeoutSeconds): ?string
    {
        stream_set_blocking($stream, false);

        $deadline = microtime(true) + $timeoutSeconds;
        $buffer   = '';
        while (microtime(true) < $deadline) {
            $read   = [$stream];
            $write  = [];
            $except = [];
            if (stream_select($read, $write, $except, 1) > 0) {
                $chunk = fgets($stream);
                if ($chunk !== false) {
                    $buffer .= $chunk;
                    if (str_contains($buffer, "\n")) {
                        return trim($buffer);
                    }
                } elseif (feof($stream)) {
                    return $buffer === '' ? null : trim($buffer);
                }
            }
        }

        return null;
    }

    /**
     * Names the sessions currently stuck in a lock wait, so a failed lock acquisition points at
     * its blocker instead of leaving a mystery.
     */
    private function describeLockWaitSessions(): string
    {
        $waiting = [];
        foreach (DB::select('SHOW FULL PROCESSLIST') as $process) {
            if (stripos((string)($process->State ?? ''), 'lock') !== false) {
                $waiting[] = sprintf(
                    'id=%s user=%s state="%s" query="%s"',
                    $process->Id,
                    $process->User ?? '?',
                    $process->State,
                    substr((string)($process->Info ?? ''), 0, 120),
                );
            }
        }

        return $waiting === [] ? '(none visible)' : implode('; ', $waiting);
    }

    private function lockerScriptContents(): string
    {
        return <<<'PHP'
            <?php
            [, $host, $port, $database, $username, $password, $table, $holdMs, $lockAcquireTimeoutSeconds] = $argv;
            try {
                $pdo = new PDO("mysql:host={$host};port={$port};dbname={$database}", $username, $password);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                // MySQL's default lock_wait_timeout is one year - bounded here so a conflicting
                // lock fails this child (and with it the test) instead of hanging the suite (#4345)
                $pdo->exec("SET SESSION lock_wait_timeout = " . max(1, (int)$lockAcquireTimeoutSeconds));
                $pdo->exec("LOCK TABLES `{$table}` WRITE");
            } catch (Throwable $e) {
                fwrite(STDOUT, "FAILED: " . str_replace("\n", ' ', $e->getMessage()) . "\n");
                fflush(STDOUT);
                exit(1);
            }
            fwrite(STDOUT, "LOCKED\n");
            fflush(STDOUT);
            usleep((int)$holdMs * 1000);
            $pdo->exec('UNLOCK TABLES');
            fwrite(STDOUT, "DONE\n");
            PHP;
    }
}
