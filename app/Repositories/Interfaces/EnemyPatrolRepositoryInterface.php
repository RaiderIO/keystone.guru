<?php

namespace App\Repositories\Interfaces;

use App\Models\EnemyPatrol;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method EnemyPatrol                  create(array<string, mixed> $attributes)
 * @method EnemyPatrol|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method EnemyPatrol                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method EnemyPatrol                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                         save(EnemyPatrol $model)
 * @method bool                         update(EnemyPatrol $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                         delete(EnemyPatrol $model)
 * @method Collection<int, EnemyPatrol> all()
 * @method bool                         exists(array<int, string> $columns)
 */
interface EnemyPatrolRepositoryInterface extends BaseRepositoryInterface
{
}
