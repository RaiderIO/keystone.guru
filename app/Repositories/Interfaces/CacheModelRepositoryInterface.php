<?php

namespace App\Repositories\Interfaces;

use App\Models\CacheModel;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method CacheModel create(array $attributes)
 * @method CacheModel|null find(int $id, array|string $columns = ['*'])
 * @method CacheModel findOrFail(int $id, array|string $columns = ['*'])
 * @method CacheModel findOrNew(int $id, array|string $columns = ['*'])
 * @method bool save(CacheModel $model)
 * @method bool update(CacheModel $model, array $attributes = [], array $options = [])
 * @method bool delete(CacheModel $model)
 * @method Collection<CacheModel> all()
 */
interface CacheModelRepositoryInterface extends BaseRepositoryInterface
{

}
