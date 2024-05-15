<?php

namespace App\Repositories\Interfaces;

use App\Models\MapIcon;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method MapIcon create(array $attributes)
 * @method MapIcon|null find(int $id, array|string $columns = ['*'])
 * @method MapIcon findOrFail(int $id, array|string $columns = ['*'])
 * @method MapIcon findOrNew(int $id, array|string $columns = ['*'])
 * @method bool save(MapIcon $model)
 * @method bool update(MapIcon $model, array $attributes = [], array $options = [])
 * @method bool delete(MapIcon $model)
 * @method Collection<MapIcon> all()
 */
interface MapIconRepositoryInterface extends BaseRepositoryInterface
{

}
