<?php

namespace App\Repositories\Interfaces\CombatLog;

use App\Models\CombatLog\CombatLogNpcCharacteristicObservation;
use App\Service\CombatLog\Dtos\ObservationDensity;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * The tuple is `(npc_id, characteristic_id)`, so the history is keyed by characteristic_id.
 *
 * @method CombatLogNpcCharacteristicObservation                  create(array<string, mixed> $attributes)
 * @method CombatLogNpcCharacteristicObservation|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method CombatLogNpcCharacteristicObservation                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method CombatLogNpcCharacteristicObservation                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                                                   save(CombatLogNpcCharacteristicObservation $model)
 * @method bool                                                   update(CombatLogNpcCharacteristicObservation $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                                                   delete(CombatLogNpcCharacteristicObservation $model)
 * @method Collection<int, CombatLogNpcCharacteristicObservation> all()
 * @method bool                                                   exists(array<int, string> $columns)
 *
 * @extends CombatLogObservationRepositoryInterface<CombatLogNpcCharacteristicObservation, int>
 */
interface CombatLogNpcCharacteristicObservationRepositoryInterface extends CombatLogObservationRepositoryInterface
{
    /**
     * The fact `(npc, characteristic)` measured against its NPC, in one query over the NPC's rows.
     *
     * @param Carbon|null $before      Only count days earlier than this date.
     * @param Carbon|null $recentSince Also count the NPC's observed days on or after this date.
     */
    public function getDensity(int $npcId, int $characteristicId, ?Carbon $before = null, ?Carbon $recentSince = null): ObservationDensity;
}
