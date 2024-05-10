<?php

namespace App\Repositories\Interfaces\CombatLog;

use App\Models\CombatLog\EnemyPosition;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method EnemyPosition create(array $attributes)
 * @method EnemyPosition find(int $id, array $columns = [])
 * @method EnemyPosition findOrFail(int $id, array $columns = [])
 * @method EnemyPosition findOrNew(int $id, array $columns = [])
 * @method bool save(EnemyPosition $model)
 * @method bool update(EnemyPosition $model, array $attributes = [], array $options = [])
 * @method bool delete(EnemyPosition $model)
 * @method Collection<EnemyPosition> all()
 */
interface EnemyPositionRepositoryInterface extends BaseRepositoryInterface
{

}
