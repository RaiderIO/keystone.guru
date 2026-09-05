<?php

namespace App\Repositories\Interfaces\CombatLog;

use App\Models\CombatLog\CombatLogNpcCharacteristicObservation;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * @method CombatLogNpcCharacteristicObservation                  create(array<string, mixed> $attributes)
 * @method CombatLogNpcCharacteristicObservation|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method CombatLogNpcCharacteristicObservation                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method CombatLogNpcCharacteristicObservation                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                                                   save(CombatLogNpcCharacteristicObservation $model)
 * @method bool                                                   update(CombatLogNpcCharacteristicObservation $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                                                   delete(CombatLogNpcCharacteristicObservation $model)
 * @method Collection<int, CombatLogNpcCharacteristicObservation> all()
 * @method bool                                                   exists(array<int, string> $columns)
 */
interface CombatLogNpcCharacteristicObservationRepositoryInterface extends BaseRepositoryInterface
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
     * `days_observed => tuple_count` across every `(npc_id, characteristic_id)` tuple, computed entirely in SQL
     * (a `GROUP BY` per tuple rolled up into a second `GROUP BY` per bucket) so this never pulls one row per
     * tuple into PHP - the response stays small regardless of how many tuples exist.
     *
     * @return array<int, int>
     */
    public function getDensityHistogram(): array;

    /**
     * The number of distinct `(npc_id, characteristic_id)` tuples, computed in SQL.
     */
    public function getTupleCount(): int;

    /**
     * Up to `$limit` `(npc_id, characteristic_id)` tuples with their distinct `observed_on` day count, ordered
     * deterministically by npc_id, characteristic_id. The limit is applied in the query itself, so at most
     * `$limit` rows are ever fetched.
     *
     * @return Collection<int, CombatLogNpcCharacteristicObservation> partial models exposing npc_id, characteristic_id, days_observed
     */
    public function getTuples(int $limit): Collection;

    /**
     * For one NPC, every observed characteristic's list of `observed_on` dates (newest first), keyed by
     * characteristic_id.
     *
     * @return Collection<int, Collection<int, Carbon>>
     */
    public function getHistoryForNpc(int $npcId): Collection;
}
