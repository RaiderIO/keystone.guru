<?php

namespace App\Repositories\Interfaces;

use App\Models\Release;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method Release create(array $attributes)
 * @method Release find(int $id, array $columns = [])
 * @method Release findOrFail(int $id, array $columns = [])
 * @method Release findOrNew(int $id, array $columns = [])
 * @method bool save(Release $model)
 * @method bool update(Release $model, array $attributes = [], array $options = [])
 * @method bool delete(Release $model)
 * @method Collection<Release> all()
 */
interface ReleaseRepositoryInterface extends BaseRepositoryInterface
{

}
