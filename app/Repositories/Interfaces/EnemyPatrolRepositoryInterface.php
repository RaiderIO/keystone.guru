<?php

namespace App\Repositories\Interfaces;

use App\Models\EnemyPatrol;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method EnemyPatrol create(array $attributes)
 * @method EnemyPatrol|null find(int $id, array|string $columns = ['*'])
 * @method EnemyPatrol findOrFail(int $id, array|string $columns = ['*'])
 * @method EnemyPatrol findOrNew(int $id, array|string $columns = ['*'])
 * @method bool save(EnemyPatrol $model)
 * @method bool update(EnemyPatrol $model, array $attributes = [], array $options = [])
 * @method bool delete(EnemyPatrol $model)
 * @method Collection<EnemyPatrol> all()
 */
interface EnemyPatrolRepositoryInterface extends BaseRepositoryInterface
{

}
