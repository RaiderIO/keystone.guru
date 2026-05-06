<?php

namespace App\Repositories\Interfaces;

use App\Models\EnemyActiveAura;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method EnemyActiveAura             create(array $attributes)
 * @method EnemyActiveAura|null        find(int $id, array|string $columns = ['*'])
 * @method EnemyActiveAura             findOrFail(int $id, array|string $columns = ['*'])
 * @method EnemyActiveAura             findOrNew(int $id, array|string $columns = ['*'])
 * @method bool                        save(EnemyActiveAura $model)
 * @method bool                        update(EnemyActiveAura $model, array $attributes = [], array $options = [])
 * @method bool                        delete(EnemyActiveAura $model)
 * @method Collection<EnemyActiveAura> all()
 */
interface EnemyActiveAuraRepositoryInterface extends BaseRepositoryInterface
{
}
