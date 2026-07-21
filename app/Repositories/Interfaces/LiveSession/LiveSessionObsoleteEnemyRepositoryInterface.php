<?php

namespace App\Repositories\Interfaces\LiveSession;

use App\Models\LiveSession\LiveSessionObsoleteEnemy;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method LiveSessionObsoleteEnemy                  create(array<string, mixed> $attributes)
 * @method LiveSessionObsoleteEnemy|null              find(int $id, array<int, string>|string $columns = ['*'])
 * @method LiveSessionObsoleteEnemy                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method LiveSessionObsoleteEnemy                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                                      save(LiveSessionObsoleteEnemy $model)
 * @method bool                                      update(LiveSessionObsoleteEnemy $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                                      delete(LiveSessionObsoleteEnemy $model)
 * @method Collection<int, LiveSessionObsoleteEnemy> all()
 * @method bool                                      exists(array<int, string> $columns)
 */
interface LiveSessionObsoleteEnemyRepositoryInterface extends BaseRepositoryInterface
{
}
