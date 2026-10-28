<?php

namespace App\Repositories\Interfaces\LiveSession;

use App\Models\LiveSession\LiveSessionKilledEnemy;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method LiveSessionKilledEnemy                  create(array<string, mixed> $attributes)
 * @method LiveSessionKilledEnemy|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method LiveSessionKilledEnemy                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method LiveSessionKilledEnemy                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                                    save(LiveSessionKilledEnemy $model)
 * @method bool                                    update(LiveSessionKilledEnemy $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                                    delete(LiveSessionKilledEnemy $model)
 * @method Collection<int, LiveSessionKilledEnemy> all()
 * @method bool                                    exists(array<int, string> $columns)
 */
interface LiveSessionKilledEnemyRepositoryInterface extends BaseRepositoryInterface
{
}
