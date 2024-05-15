<?php

namespace App\Repositories\Interfaces\Enemies;

use App\Models\Enemies\OverpulledEnemy;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method OverpulledEnemy create(array $attributes)
 * @method OverpulledEnemy find(int $id, array $columns = [])
 * @method OverpulledEnemy findOrFail(int $id, array $columns = [])
 * @method OverpulledEnemy findOrNew(int $id, array $columns = [])
 * @method bool save(OverpulledEnemy $model)
 * @method bool update(OverpulledEnemy $model, array $attributes = [], array $options = [])
 * @method bool delete(OverpulledEnemy $model)
 * @method Collection<OverpulledEnemy> all()
 */
interface OverpulledEnemyRepositoryInterface extends BaseRepositoryInterface
{

}
