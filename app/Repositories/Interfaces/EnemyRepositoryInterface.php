<?php

namespace App\Repositories\Interfaces;

use App\Models\Enemy;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method Enemy create(array $attributes)
 * @method Enemy find(int $id, array $columns = [])
 * @method Enemy findOrFail(int $id, array $columns = [])
 * @method Enemy findOrNew(int $id, array $columns = [])
 * @method bool save(Enemy $model)
 * @method bool update(Enemy $model, array $attributes = [], array $options = [])
 * @method bool delete(Enemy $model)
 * @method Collection<Enemy> all()
 */
interface EnemyRepositoryInterface extends BaseRepositoryInterface
{

}
