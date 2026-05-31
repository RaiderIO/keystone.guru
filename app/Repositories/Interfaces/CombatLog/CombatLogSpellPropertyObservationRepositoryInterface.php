<?php

namespace App\Repositories\Interfaces\CombatLog;

use App\Models\CombatLog\CombatLogSpellPropertyObservation;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method CombatLogSpellPropertyObservation             create(array $attributes)
 * @method CombatLogSpellPropertyObservation|null        find(int $id, array|string $columns = ['*'])
 * @method CombatLogSpellPropertyObservation             findOrFail(int $id, array|string $columns = ['*'])
 * @method CombatLogSpellPropertyObservation             findOrNew(int $id, array|string $columns = ['*'])
 * @method bool                                          save(CombatLogSpellPropertyObservation $model)
 * @method bool                                          update(CombatLogSpellPropertyObservation $model, array $attributes = [], array $options = [])
 * @method bool                                          delete(CombatLogSpellPropertyObservation $model)
 * @method Collection<CombatLogSpellPropertyObservation> all()
 * @method bool                                          exists(array $columns)
 */
interface CombatLogSpellPropertyObservationRepositoryInterface extends BaseRepositoryInterface
{
}
