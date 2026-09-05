<?php

namespace App\Repositories\Interfaces\CombatLog;

use App\Models\CombatLog\CombatLogSpellPropertyObservation;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * @method CombatLogSpellPropertyObservation                  create(array<string, mixed> $attributes)
 * @method CombatLogSpellPropertyObservation|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method CombatLogSpellPropertyObservation                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method CombatLogSpellPropertyObservation                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                                               save(CombatLogSpellPropertyObservation $model)
 * @method bool                                               update(CombatLogSpellPropertyObservation $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                                               delete(CombatLogSpellPropertyObservation $model)
 * @method Collection<int, CombatLogSpellPropertyObservation> all()
 * @method bool                                               exists(array<int, string> $columns)
 */
interface CombatLogSpellPropertyObservationRepositoryInterface extends BaseRepositoryInterface
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
     * `days_observed => tuple_count` across every `(spell_id, property)` tuple, computed entirely in SQL (a
     * `GROUP BY` per tuple rolled up into a second `GROUP BY` per bucket) so this never pulls one row per tuple
     * into PHP - the response stays small regardless of how many tuples exist.
     *
     * @return array<int, int>
     */
    public function getDensityHistogram(): array;

    /**
     * The number of distinct `(spell_id, property)` tuples, computed in SQL.
     */
    public function getTupleCount(): int;

    /**
     * Up to `$limit` `(spell_id, property)` tuples with their distinct `observed_on` day count, ordered
     * deterministically by spell_id, property. The limit is applied in the query itself, so at most `$limit`
     * rows are ever fetched.
     *
     * @return Collection<int, CombatLogSpellPropertyObservation> partial models exposing spell_id, property, days_observed
     */
    public function getTuples(int $limit): Collection;

    /**
     * For one spell, every observed property's list of `observed_on` dates (newest first), keyed by the
     * property's enum value.
     *
     * @return Collection<string, Collection<int, Carbon>>
     */
    public function getHistoryForSpell(int $spellId): Collection;
}
