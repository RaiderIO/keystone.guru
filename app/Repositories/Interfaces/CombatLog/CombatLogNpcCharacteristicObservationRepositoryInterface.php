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
     * Per `(npc_id, characteristic_id)` tuple, the number of distinct `observed_on` dates recorded - i.e. how many
     * days that fact has been seen. Ordered deterministically by npc_id, characteristic_id.
     *
     * @return Collection<int, CombatLogNpcCharacteristicObservation> partial models exposing npc_id, characteristic_id, days_observed
     */
    public function getTupleDensity(): Collection;

    /**
     * For one NPC, every observed characteristic's list of `observed_on` dates (newest first), keyed by
     * characteristic_id.
     *
     * @return Collection<int, Collection<int, Carbon>>
     */
    public function getHistoryForNpc(int $npcId): Collection;
}
