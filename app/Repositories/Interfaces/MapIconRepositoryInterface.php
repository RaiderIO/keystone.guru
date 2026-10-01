<?php

namespace App\Repositories\Interfaces;

use App\Models\MapIcon;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method MapIcon                  create(array<string, mixed> $attributes)
 * @method MapIcon|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method MapIcon                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method MapIcon                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                     save(MapIcon $model)
 * @method bool                     update(MapIcon $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                     delete(MapIcon $model)
 * @method Collection<int, MapIcon> all()
 * @method bool                     exists(array<int, string> $columns)
 */
interface MapIconRepositoryInterface extends BaseRepositoryInterface
{
}
