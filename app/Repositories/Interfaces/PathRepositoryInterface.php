<?php

namespace App\Repositories\Interfaces;

use App\Models\Path;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method Path             create(array $attributes)
 * @method Path|null        find(int $id, array|string $columns = ['*'])
 * @method Path             findOrFail(int $id, array|string $columns = ['*'])
 * @method Path             findOrNew(int $id, array|string $columns = ['*'])
 * @method bool             save(Path $model)
 * @method bool             update(Path $model, array $attributes = [], array $options = [])
 * @method bool             delete(Path $model)
 * @method Collection<Path> all()
 */
interface PathRepositoryInterface extends BaseRepositoryInterface
{
}
