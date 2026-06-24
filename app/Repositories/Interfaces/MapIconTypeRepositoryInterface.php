<?php

namespace App\Repositories\Interfaces;

use App\Models\MapIconType;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method MapIconType                  create(array<string, mixed> $attributes)
 * @method MapIconType|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method MapIconType                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method MapIconType                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                         save(MapIconType $model)
 * @method bool                         update(MapIconType $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                         delete(MapIconType $model)
 * @method Collection<int, MapIconType> all()
 * @method bool                         exists(array<int, string> $columns)
 */
interface MapIconTypeRepositoryInterface extends BaseRepositoryInterface
{
}
