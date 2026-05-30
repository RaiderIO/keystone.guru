<?php

namespace App\Repositories\Interfaces\CombatLog;

use App\Models\CombatLog\CombatLogNpcEvent;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method CombatLogNpcEvent             create(array $attributes)
 * @method CombatLogNpcEvent|null        find(int $id, array|string $columns = ['*'])
 * @method CombatLogNpcEvent             findOrFail(int $id, array|string $columns = ['*'])
 * @method CombatLogNpcEvent             findOrNew(int $id, array|string $columns = ['*'])
 * @method bool                          save(CombatLogNpcEvent $model)
 * @method bool                          update(CombatLogNpcEvent $model, array $attributes = [], array $options = [])
 * @method bool                          delete(CombatLogNpcEvent $model)
 * @method Collection<CombatLogNpcEvent> all()
 * @method bool                          exists(array $columns)
 */
interface CombatLogNpcEventRepositoryInterface extends BaseRepositoryInterface
{
}
