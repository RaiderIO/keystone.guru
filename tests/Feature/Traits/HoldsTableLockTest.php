<?php

namespace Tests\Feature\Traits;

use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Covers #4345 - the failure path of {@see HoldsTableLock}. The original trait waited on
 * `LOCK TABLES` with MySQL's default one-year lock_wait_timeout and on timeout-less pipe reads,
 * so a conflicting lock (any concurrent write transaction on the table) hung the whole suite
 * silently. These tests pin the bounded behaviour: a blocked lock must fail the test loudly,
 * within the configured timeout, naming the problem.
 */
#[Group('HoldsTableLock')]
class HoldsTableLockTest extends TestCase
{
    use HoldsTableLock;

    #[Test]
    public function runWhileTableIsWriteLocked_givenTableBlockedByOpenWriteTransaction_failsInsteadOfHanging(): void
    {
        // Arrange - an open transaction that has written to the table holds a metadata lock that
        // blocks the locker child's LOCK TABLES ... WRITE until commit/rollback. The UPDATE
        // matches no rows on purpose: the metadata lock is taken either way, and there is nothing
        // to clean up beyond the rollback.
        DB::beginTransaction();

        $callbackRan = false;
        $failure     = null;
        $start       = microtime(true);

        try {
            DB::table('polylines')->where('id', -1)->update(['weight' => 1]);

            // Act
            try {
                $this->runWhileTableIsWriteLocked(
                    'polylines',
                    static function () use (&$callbackRan): void {
                        $callbackRan = true;
                    },
                    holdMs: 500,
                    lockAcquireTimeoutSeconds: 2,
                );
            } catch (AssertionFailedError $assertionFailedError) {
                $failure = $assertionFailedError;
            }
        } finally {
            DB::rollBack();
        }

        // Assert - failed loudly, quickly, without ever running the callback
        $this->assertNotNull($failure, 'A blocked LOCK TABLES must fail the test, not acquire the lock');
        $this->assertStringContainsString('could not acquire LOCK TABLES `polylines` WRITE', $failure->getMessage());
        $this->assertFalse($callbackRan, 'The callback must not run when the lock was never acquired');
        $this->assertLessThan(
            9,
            microtime(true) - $start,
            'The failure must surface within the configured timeout, not after an unbounded wait',
        );
    }

    #[Test]
    public function runWhileTableIsWriteLocked_givenUncontendedTable_runsCallbackUnderHeldLock(): void
    {
        // Arrange
        $callbackRan = false;

        // Act - the happy path: lock acquired, callback runs while this connection's writes to
        // the table would time out (the four *TransactionRetryTests exercise that part in anger)
        $this->runWhileTableIsWriteLocked(
            'polylines',
            static function () use (&$callbackRan): void {
                $callbackRan = true;
            },
            holdMs: 300,
        );

        // Assert
        $this->assertTrue($callbackRan);
    }
}
