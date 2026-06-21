<?php

namespace App\Repositories\Interfaces;

use App\Models\EnemyActiveAura;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method EnemyActiveAura                  create(array<string, mixed> $attributes)
 * @method EnemyActiveAura|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method EnemyActiveAura                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method EnemyActiveAura                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                             save(EnemyActiveAura $model)
 * @method bool                             update(EnemyActiveAura $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                             delete(EnemyActiveAura $model)
 * @method Collection<int, EnemyActiveAura> all()
 * @method bool                             exists(array<int, string> $columns)
 */
interface EnemyActiveAuraRepositoryInterface extends BaseRepositoryInterface
{
}
