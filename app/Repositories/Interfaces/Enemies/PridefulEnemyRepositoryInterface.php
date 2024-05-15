<?php

namespace App\Repositories\Interfaces\Enemies;

use App\Models\Enemies\PridefulEnemy;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method PridefulEnemy create(array $attributes)
 * @method PridefulEnemy|null find(int $id, array|string $columns = ['*'])
 * @method PridefulEnemy findOrFail(int $id, array|string $columns = ['*'])
 * @method PridefulEnemy findOrNew(int $id, array|string $columns = ['*'])
 * @method bool save(PridefulEnemy $model)
 * @method bool update(PridefulEnemy $model, array $attributes = [], array $options = [])
 * @method bool delete(PridefulEnemy $model)
 * @method Collection<PridefulEnemy> all()
 */
interface PridefulEnemyRepositoryInterface extends BaseRepositoryInterface
{

}
