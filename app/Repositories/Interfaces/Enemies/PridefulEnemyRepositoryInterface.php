<?php

namespace App\Repositories\Interfaces\Enemies;

use App\Models\Enemies\PridefulEnemy;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method PridefulEnemy                  create(array<string, mixed> $attributes)
 * @method PridefulEnemy|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method PridefulEnemy                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method PridefulEnemy                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                           save(PridefulEnemy $model)
 * @method bool                           update(PridefulEnemy $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                           delete(PridefulEnemy $model)
 * @method Collection<int, PridefulEnemy> all()
 * @method bool                           exists(array<int, string> $columns)
 */
interface PridefulEnemyRepositoryInterface extends BaseRepositoryInterface
{
}
