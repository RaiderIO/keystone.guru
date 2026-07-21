<?php

namespace App\Repositories\Interfaces\LiveSession;

use App\Models\LiveSession\LiveSessionInCombatEnemy;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method LiveSessionInCombatEnemy                  create(array<string, mixed> $attributes)
 * @method LiveSessionInCombatEnemy|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method LiveSessionInCombatEnemy                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method LiveSessionInCombatEnemy                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                                      save(LiveSessionInCombatEnemy $model)
 * @method bool                                      update(LiveSessionInCombatEnemy $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                                      delete(LiveSessionInCombatEnemy $model)
 * @method Collection<int, LiveSessionInCombatEnemy> all()
 * @method bool                                      exists(array<int, string> $columns)
 */
interface LiveSessionInCombatEnemyRepositoryInterface extends BaseRepositoryInterface
{
}
