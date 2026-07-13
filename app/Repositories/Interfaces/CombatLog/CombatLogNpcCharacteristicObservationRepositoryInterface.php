<?php

namespace App\Repositories\Interfaces\CombatLog;

use App\Models\CombatLog\CombatLogNpcCharacteristicObservation;
use App\Repositories\BaseRepositoryInterface;
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
}
