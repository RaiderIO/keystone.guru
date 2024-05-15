<?php

namespace App\Repositories\Interfaces;

use App\Models\EnemyPack;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method EnemyPack create(array $attributes)
 * @method EnemyPack find(int $id, array $columns = [])
 * @method EnemyPack findOrFail(int $id, array $columns = [])
 * @method EnemyPack findOrNew(int $id, array $columns = [])
 * @method bool save(EnemyPack $model)
 * @method bool update(EnemyPack $model, array $attributes = [], array $options = [])
 * @method bool delete(EnemyPack $model)
 * @method Collection<EnemyPack> all()
 */
interface EnemyPackRepositoryInterface extends BaseRepositoryInterface
{

}
