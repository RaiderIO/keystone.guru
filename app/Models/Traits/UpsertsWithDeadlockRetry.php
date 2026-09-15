<?php

namespace App\Models\Traits;

use Eloquent;

/**
 * @mixin Eloquent
 */
trait UpsertsWithDeadlockRetry
{
    private const int UPSERT_DEADLOCK_ATTEMPTS = 3;

    /**
     * Concurrent multi-row upserts into one unique index still deadlock on MySQL gap locks when sorted; the victim is
     * rolled back whole, so transaction() re-runs it - but only when no outer transaction is open on the connection.
     *
     * @param  array<array-key, array<string, mixed>> $rows
     * @param  array<int, string>                     $uniqueBy
     * @param  array<int, string>                     $update
     * @return int                                    The number of affected rows, as reported by MySQL
     */
    public static function upsertWithDeadlockRetry(array $rows, array $uniqueBy, array $update): int
    {
        $sortedRows = collect($rows)
            ->sortBy(static fn(array $row) => array_map(static fn(string $column) => $row[$column], $uniqueBy))
            ->values()
            ->all();

        return static::query()->getConnection()->transaction(
            static fn(): int => static::query()->upsert($sortedRows, $uniqueBy, $update),
            self::UPSERT_DEADLOCK_ATTEMPTS,
        );
    }
}
