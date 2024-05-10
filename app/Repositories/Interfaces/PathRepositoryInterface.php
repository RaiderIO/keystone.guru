<?php

namespace App\Repositories\Interfaces;

use App\Models\Path;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method Path create(array $attributes)
 * @method Path find(int $id, array $columns = [])
 * @method Path findOrFail(int $id, array $columns = [])
 * @method Path findOrNew(int $id, array $columns = [])
 * @method bool save(Path $model)
 * @method bool update(Path $model, array $attributes = [], array $options = [])
 * @method bool delete(Path $model)
 * @method Collection<Path> all()
 */
interface PathRepositoryInterface extends BaseRepositoryInterface
{

}
