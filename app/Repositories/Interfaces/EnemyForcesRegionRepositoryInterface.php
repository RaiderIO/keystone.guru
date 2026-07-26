<?php

namespace App\Repositories\Interfaces;

use App\Models\EnemyForcesRegion;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method EnemyForcesRegion                  create(array<string, mixed> $attributes)
 * @method EnemyForcesRegion|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method EnemyForcesRegion                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method EnemyForcesRegion                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                               save(EnemyForcesRegion $model)
 * @method bool                               update(EnemyForcesRegion $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                               delete(EnemyForcesRegion $model)
 * @method Collection<int, EnemyForcesRegion> all()
 * @method bool                               exists(array<int, string> $columns)
 */
interface EnemyForcesRegionRepositoryInterface extends BaseRepositoryInterface
{
}
