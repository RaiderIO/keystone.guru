<?php

namespace App\Repositories\Interfaces;

use App\Models\MountableArea;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method MountableArea             create(array $attributes)
 * @method MountableArea|null        find(int $id, array|string $columns = ['*'])
 * @method MountableArea             findOrFail(int $id, array|string $columns = ['*'])
 * @method MountableArea             findOrNew(int $id, array|string $columns = ['*'])
 * @method bool                      save(MountableArea $model)
 * @method bool                      update(MountableArea $model, array $attributes = [], array $options = [])
 * @method bool                      delete(MountableArea $model)
 * @method Collection<MountableArea> all()
 */
interface MountableAreaRepositoryInterface extends BaseRepositoryInterface
{
}
