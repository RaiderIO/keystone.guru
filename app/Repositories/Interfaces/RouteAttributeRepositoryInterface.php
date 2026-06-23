<?php

namespace App\Repositories\Interfaces;

use App\Models\RouteAttribute;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method RouteAttribute                  create(array<string, mixed> $attributes)
 * @method RouteAttribute|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method RouteAttribute                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method RouteAttribute                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                            save(RouteAttribute $model)
 * @method bool                            update(RouteAttribute $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                            delete(RouteAttribute $model)
 * @method Collection<int, RouteAttribute> all()
 * @method bool                            exists(array<int, string> $columns)
 */
interface RouteAttributeRepositoryInterface extends BaseRepositoryInterface
{
}
