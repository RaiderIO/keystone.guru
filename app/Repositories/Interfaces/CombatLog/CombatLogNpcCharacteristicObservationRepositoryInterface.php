<?php

namespace App\Repositories\Interfaces\CombatLog;

use App\Models\CombatLog\CombatLogNpcCharacteristicObservation;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method CombatLogNpcCharacteristicObservation             create(array $attributes)
 * @method CombatLogNpcCharacteristicObservation|null        find(int $id, array|string $columns = ['*'])
 * @method CombatLogNpcCharacteristicObservation             findOrFail(int $id, array|string $columns = ['*'])
 * @method CombatLogNpcCharacteristicObservation             findOrNew(int $id, array|string $columns = ['*'])
 * @method bool                                              save(CombatLogNpcCharacteristicObservation $model)
 * @method bool                                              update(CombatLogNpcCharacteristicObservation $model, array $attributes = [], array $options = [])
 * @method bool                                              delete(CombatLogNpcCharacteristicObservation $model)
 * @method Collection<CombatLogNpcCharacteristicObservation> all()
 * @method bool                                              exists(array $columns)
 */
interface CombatLogNpcCharacteristicObservationRepositoryInterface extends BaseRepositoryInterface
{
}
