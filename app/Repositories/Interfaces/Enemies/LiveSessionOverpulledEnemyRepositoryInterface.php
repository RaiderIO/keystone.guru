<?php

namespace App\Repositories\Interfaces\Enemies;

use App\Models\LiveSession\LiveSessionOverpulledEnemy;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method LiveSessionOverpulledEnemy                  create(array<string, mixed> $attributes)
 * @method LiveSessionOverpulledEnemy|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method LiveSessionOverpulledEnemy                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method LiveSessionOverpulledEnemy                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                                        save(LiveSessionOverpulledEnemy $model)
 * @method bool                                        update(LiveSessionOverpulledEnemy $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                                        delete(LiveSessionOverpulledEnemy $model)
 * @method Collection<int, LiveSessionOverpulledEnemy> all()
 * @method bool                                        exists(array<int, string> $columns)
 */
interface LiveSessionOverpulledEnemyRepositoryInterface extends BaseRepositoryInterface
{
}
