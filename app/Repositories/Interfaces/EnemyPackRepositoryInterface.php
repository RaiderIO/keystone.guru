<?php

namespace App\Repositories\Interfaces;

use App\Models\EnemyPack;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method EnemyPack                  create(array<string, mixed> $attributes)
 * @method EnemyPack|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method EnemyPack                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method EnemyPack                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                       save(EnemyPack $model)
 * @method bool                       update(EnemyPack $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                       delete(EnemyPack $model)
 * @method Collection<int, EnemyPack> all()
 * @method bool                       exists(array<int, string> $columns)
 */
interface EnemyPackRepositoryInterface extends BaseRepositoryInterface
{
}
