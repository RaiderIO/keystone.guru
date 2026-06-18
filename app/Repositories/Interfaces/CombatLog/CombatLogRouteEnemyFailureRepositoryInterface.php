<?php

namespace App\Repositories\Interfaces\CombatLog;

use App\Models\CombatLog\CombatLogRouteEnemyFailure;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method CombatLogRouteEnemyFailure             create(array $attributes)
 * @method CombatLogRouteEnemyFailure|null        find(int $id, array|string $columns = ['*'])
 * @method CombatLogRouteEnemyFailure             findOrFail(int $id, array|string $columns = ['*'])
 * @method CombatLogRouteEnemyFailure             findOrNew(int $id, array|string $columns = ['*'])
 * @method bool                                   save(CombatLogRouteEnemyFailure $model)
 * @method bool                                   update(CombatLogRouteEnemyFailure $model, array $attributes = [], array $options = [])
 * @method bool                                   delete(CombatLogRouteEnemyFailure $model)
 * @method Collection<CombatLogRouteEnemyFailure> all()
 * @method bool                                   exists(array $columns)
 */
interface CombatLogRouteEnemyFailureRepositoryInterface extends BaseRepositoryInterface
{
}
