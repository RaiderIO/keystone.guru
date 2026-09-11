<?php

namespace Tests\Feature\App\Models\Trait;

use App\Models\CombatLog\CombatLogSpellPropertyObservation;
use App\Models\CombatLog\SpellProperty;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use PDOException;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('UpsertsWithDeadlockRetry')]
final class UpsertsWithDeadlockRetryTest extends PublicTestCase
{
    private const int    SPELL_ID_LOW    = 999701;
    private const int    SPELL_ID_HIGH   = 999702;
    private const string COMBAT_LOG_PATH = '/tmp/test.log';
    private const string UPDATED_PATH    = '/tmp/updated.log';

    private int $upsertAttempts = 0;

    #[\Override]
    protected function tearDown(): void
    {
        try {
            CombatLogSpellPropertyObservation::query()
                ->whereIn('spell_id', [self::SPELL_ID_LOW, self::SPELL_ID_HIGH])
                ->delete();
        } finally {
            // Drops the beforeExecuting callbacks registered on the connection instance
            DB::purge('combatlog');

            parent::tearDown();
        }
    }

    #[Test]
    public function upsertWithDeadlockRetry_givenDeadlockOnFirstAttempt_retriesAndWritesRows(): void
    {
        // Arrange
        $this->failUpsertAttempts(1, self::deadlockException(...));

        // Act
        CombatLogSpellPropertyObservation::upsertWithDeadlockRetry(
            $this->buildRows([self::SPELL_ID_LOW]),
            ['spell_id', 'property', 'observed_on'],
            ['combat_log_path', 'updated_at'],
        );

        // Assert
        $this->assertSame(2, $this->upsertAttempts);
        $this->assertSame(1, $this->countObservations());
    }

    #[Test]
    public function upsertWithDeadlockRetry_givenDeadlockOnEveryAttempt_throwsQueryExceptionAfterThreeAttempts(): void
    {
        // Arrange
        $this->failUpsertAttempts(PHP_INT_MAX, self::deadlockException(...));
        $thrown = null;

        // Act
        try {
            CombatLogSpellPropertyObservation::upsertWithDeadlockRetry(
                $this->buildRows([self::SPELL_ID_LOW]),
                ['spell_id', 'property', 'observed_on'],
                ['combat_log_path', 'updated_at'],
            );
        } catch (QueryException $exception) {
            $thrown = $exception;
        }

        // Assert
        $this->assertNotNull($thrown);
        $this->assertSame(3, $this->upsertAttempts);
        $this->assertSame(0, $this->countObservations());
        $this->assertSame(0, DB::connection('combatlog')->transactionLevel());
    }

    #[Test]
    public function upsertWithDeadlockRetry_givenNonConcurrencyError_throwsWithoutRetry(): void
    {
        // Arrange
        $this->failUpsertAttempts(PHP_INT_MAX, self::duplicateColumnException(...));
        $thrown = null;

        // Act
        try {
            CombatLogSpellPropertyObservation::upsertWithDeadlockRetry(
                $this->buildRows([self::SPELL_ID_LOW]),
                ['spell_id', 'property', 'observed_on'],
                ['combat_log_path', 'updated_at'],
            );
        } catch (QueryException $exception) {
            $thrown = $exception;
        }

        // Assert
        $this->assertNotNull($thrown);
        $this->assertSame(1, $this->upsertAttempts);
        $this->assertSame(0, DB::connection('combatlog')->transactionLevel());
    }

    #[Test]
    public function upsertWithDeadlockRetry_givenUnsortedRows_upsertsInUniqueKeyOrder(): void
    {
        // Arrange
        $boundSpellIds = [];
        DB::connection('combatlog')->beforeExecuting(function (string $query, array $bindings) use (&$boundSpellIds): void {
            if (str_starts_with($query, 'insert')) {
                $boundSpellIds = array_values(array_filter(
                    $bindings,
                    static fn(mixed $binding) => in_array($binding, [self::SPELL_ID_LOW, self::SPELL_ID_HIGH], true),
                ));
            }
        });

        // Act
        CombatLogSpellPropertyObservation::upsertWithDeadlockRetry(
            $this->buildRows([self::SPELL_ID_HIGH, self::SPELL_ID_LOW]),
            ['spell_id', 'property', 'observed_on'],
            ['combat_log_path', 'updated_at'],
        );

        // Assert
        $this->assertSame([self::SPELL_ID_LOW, self::SPELL_ID_HIGH], $boundSpellIds);
        $this->assertSame(2, $this->countObservations());
    }

    #[Test]
    public function upsertWithDeadlockRetry_givenExistingRow_updatesItInsteadOfInserting(): void
    {
        // Arrange
        CombatLogSpellPropertyObservation::upsertWithDeadlockRetry(
            $this->buildRows([self::SPELL_ID_LOW]),
            ['spell_id', 'property', 'observed_on'],
            ['combat_log_path', 'updated_at'],
        );

        // Act
        CombatLogSpellPropertyObservation::upsertWithDeadlockRetry(
            $this->buildRows([self::SPELL_ID_LOW], self::UPDATED_PATH),
            ['spell_id', 'property', 'observed_on'],
            ['combat_log_path', 'updated_at'],
        );

        // Assert
        $observations = CombatLogSpellPropertyObservation::query()->where('spell_id', self::SPELL_ID_LOW)->get();
        $this->assertCount(1, $observations);
        $this->assertSame(self::UPDATED_PATH, $observations->first()->combat_log_path);
    }

    /**
     * Throws the exception built by $exceptionFactory from the first $failingAttempts upsert statements on the
     * combatlog connection, and counts every upsert attempt.
     *
     * @param callable(string, array<int, mixed>): QueryException $exceptionFactory
     */
    private function failUpsertAttempts(int $failingAttempts, callable $exceptionFactory): void
    {
        DB::connection('combatlog')->beforeExecuting(function (string $query, array $bindings) use ($failingAttempts, $exceptionFactory): void {
            if (!str_starts_with($query, 'insert')) {
                return;
            }

            $this->upsertAttempts++;
            if ($this->upsertAttempts <= $failingAttempts) {
                throw $exceptionFactory($query, $bindings);
            }
        });
    }

    /**
     * @param  array<int, int>                  $spellIds
     * @return array<int, array<string, mixed>>
     */
    private function buildRows(array $spellIds, string $combatLogPath = self::COMBAT_LOG_PATH): array
    {
        $now = Carbon::now()->toDateTimeString();

        return array_map(static fn(int $spellId) => [
            'spell_id'        => $spellId,
            'property'        => SpellProperty::Aura->value,
            'observed_on'     => Carbon::today()->toDateString(),
            'combat_log_path' => $combatLogPath,
            'created_at'      => $now,
            'updated_at'      => $now,
        ], $spellIds);
    }

    private function countObservations(): int
    {
        return CombatLogSpellPropertyObservation::query()
            ->whereIn('spell_id', [self::SPELL_ID_LOW, self::SPELL_ID_HIGH])
            ->count();
    }

    /**
     * @param array<int, mixed> $bindings
     */
    private static function deadlockException(string $query, array $bindings): QueryException
    {
        return new QueryException('combatlog', $query, $bindings, new PDOException(
            'SQLSTATE[40001]: Serialization failure: 1213 Deadlock found when trying to get lock; try restarting transaction',
        ));
    }

    /**
     * @param array<int, mixed> $bindings
     */
    private static function duplicateColumnException(string $query, array $bindings): QueryException
    {
        return new QueryException('combatlog', $query, $bindings, new PDOException(
            "SQLSTATE[42S21]: Column already exists: 1060 Duplicate column name 'spell_id'",
        ));
    }
}
