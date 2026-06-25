<?php

namespace App\Repositories\Interfaces\Laratrust;

use App\Models\Laratrust\Role;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method Role                  create(array<string, mixed> $attributes)
 * @method Role|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method Role                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method Role                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                  save(Role $model)
 * @method bool                  update(Role $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                  delete(Role $model)
 * @method Collection<int, Role> all()
 * @method bool                  exists(array<int, string> $columns)
 */
interface RoleRepositoryInterface extends BaseRepositoryInterface
{
}
