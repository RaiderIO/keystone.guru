<?php

namespace App\Repositories\Interfaces\CombatLog;

use App\Models\CombatLog\CombatLogRouteEnemyFailure;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method CombatLogRouteEnemyFailure                  create(array<string, mixed> $attributes)
 * @method CombatLogRouteEnemyFailure|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method CombatLogRouteEnemyFailure                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method CombatLogRouteEnemyFailure                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                                        save(CombatLogRouteEnemyFailure $model)
 * @method bool                                        update(CombatLogRouteEnemyFailure $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                                        delete(CombatLogRouteEnemyFailure $model)
 * @method Collection<int, CombatLogRouteEnemyFailure> all()
 * @method bool                                        exists(array<int, string> $columns)
 */
interface CombatLogRouteEnemyFailureRepositoryInterface extends BaseRepositoryInterface
{
}
