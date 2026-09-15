<?php

namespace App\Repositories\Interfaces\CombatLog;

use App\Repositories\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * The read surface shared by every combat log observation table. Each such table is a rolling window of
 * `(tuple, observed_on)` rows, so the density and history queries only differ in which columns make up the tuple.
 *
 * @template TObservation of Model the observation model this repository reads
 * @template TTupleKey of array-key the type of the key the history is grouped by
 */
interface CombatLogObservationRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Total row count of the table.
     */
    public function countAll(): int;

    /**
     * The distinct `observed_on` dates present across the whole table.
     *
     * @return array{min: ?Carbon, max: ?Carbon, count: int}
     */
    public function getObservedOnDateRange(): array;

    /**
     * `days_observed => tuple_count` across every tuple, computed entirely in SQL (a `GROUP BY` per tuple rolled
     * up into a second `GROUP BY` per bucket) so this never pulls one row per tuple into PHP - the response stays
     * small regardless of how many tuples exist.
     *
     * @return array<int, int>
     */
    public function getDensityHistogram(): array;

    /**
     * The number of distinct tuples, computed in SQL.
     */
    public function getTupleCount(): int;

    /**
     * Up to `$limit` tuples with their distinct `observed_on` day count, ordered deterministically by the columns
     * making up the tuple. The limit is applied in the query itself, so at most `$limit` rows are ever fetched.
     *
     * @return Collection<int, TObservation> partial models exposing the tuple's columns and days_observed
     */
    public function getTuples(int $limit): Collection;

    /**
     * For one subject, every observed tuple's list of `observed_on` dates (newest first), keyed by the part of the
     * tuple that is not the subject itself.
     *
     * @return Collection<TTupleKey, Collection<int, Carbon>>
     */
    public function getHistory(int $subjectId): Collection;
}
