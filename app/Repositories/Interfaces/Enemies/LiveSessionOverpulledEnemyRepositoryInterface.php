<?php

namespace App\Repositories\Interfaces\Enemies;

use App\Models\LiveSession\LiveSessionOverpulledEnemy;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method LiveSessionOverpulledEnemy             create(array $attributes)
 * @method LiveSessionOverpulledEnemy|null        find(int $id, array|string $columns = ['*'])
 * @method LiveSessionOverpulledEnemy             findOrFail(int $id, array|string $columns = ['*'])
 * @method LiveSessionOverpulledEnemy             findOrNew(int $id, array|string $columns = ['*'])
 * @method bool                                   save(LiveSessionOverpulledEnemy $model)
 * @method bool                                   update(LiveSessionOverpulledEnemy $model, array $attributes = [], array $options = [])
 * @method bool                                   delete(LiveSessionOverpulledEnemy $model)
 * @method Collection<LiveSessionOverpulledEnemy> all()
 * @method bool                                   exists(array $columns)
 */
interface LiveSessionOverpulledEnemyRepositoryInterface extends BaseRepositoryInterface
{
}
