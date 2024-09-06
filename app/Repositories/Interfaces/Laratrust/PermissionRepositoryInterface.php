<?php

namespace App\Repositories\Interfaces\Laratrust;

use App\Models\Laratrust\Permission;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method Permission create(array $attributes)
 * @method Permission|null find(int $id, array|string $columns = ['*'])
 * @method Permission findOrFail(int $id, array|string $columns = ['*'])
 * @method Permission findOrNew(int $id, array|string $columns = ['*'])
 * @method bool save(Permission $model)
 * @method bool update(Permission $model, array $attributes = [], array $options = [])
 * @method bool delete(Permission $model)
 * @method Collection<Permission> all()
 */
interface PermissionRepositoryInterface extends BaseRepositoryInterface
{

}
