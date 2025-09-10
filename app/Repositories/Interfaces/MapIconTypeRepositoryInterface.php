<?php

namespace App\Repositories\Interfaces;

use App\Models\MapIconType;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method MapIconType             create(array $attributes)
 * @method MapIconType|null        find(int $id, array|string $columns = ['*'])
 * @method MapIconType             findOrFail(int $id, array|string $columns = ['*'])
 * @method MapIconType             findOrNew(int $id, array|string $columns = ['*'])
 * @method bool                    save(MapIconType $model)
 * @method bool                    update(MapIconType $model, array $attributes = [], array $options = [])
 * @method bool                    delete(MapIconType $model)
 * @method Collection<MapIconType> all()
 */
interface MapIconTypeRepositoryInterface extends BaseRepositoryInterface
{
}
