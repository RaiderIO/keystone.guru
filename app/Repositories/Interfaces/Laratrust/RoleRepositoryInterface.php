<?php

namespace App\Repositories\Interfaces\Laratrust;

use App\Models\Laratrust\Role;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method Role create(array $attributes)
 * @method Role|null find(int $id, array|string $columns = ['*'])
 * @method Role findOrFail(int $id, array|string $columns = ['*'])
 * @method Role findOrNew(int $id, array|string $columns = ['*'])
 * @method bool save(Role $model)
 * @method bool update(Role $model, array $attributes = [], array $options = [])
 * @method bool delete(Role $model)
 * @method Collection<Role> all()
 */
interface RoleRepositoryInterface extends BaseRepositoryInterface
{

}
