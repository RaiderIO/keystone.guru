<?php

namespace App\Repositories\Interfaces\Enemies;

use App\Models\Enemies\OverpulledEnemy;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method OverpulledEnemy                  create(array<string, mixed> $attributes)
 * @method OverpulledEnemy|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method OverpulledEnemy                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method OverpulledEnemy                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                             save(OverpulledEnemy $model)
 * @method bool                             update(OverpulledEnemy $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                             delete(OverpulledEnemy $model)
 * @method Collection<int, OverpulledEnemy> all()
 * @method bool                             exists(array<int, string> $columns)
 */
interface OverpulledEnemyRepositoryInterface extends BaseRepositoryInterface
{
}
