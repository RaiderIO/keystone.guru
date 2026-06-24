<?php

namespace App\Repositories\Interfaces\Laratrust;

use App\Models\Laratrust\Permission;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method Permission                  create(array<string, mixed> $attributes)
 * @method Permission|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method Permission                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method Permission                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                        save(Permission $model)
 * @method bool                        update(Permission $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                        delete(Permission $model)
 * @method Collection<int, Permission> all()
 * @method bool                        exists(array<int, string> $columns)
 */
interface PermissionRepositoryInterface extends BaseRepositoryInterface
{
}
