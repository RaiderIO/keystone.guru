<?php

namespace App\Repositories\Interfaces;

use App\Models\MapIcon;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method MapIcon create(array $attributes)
 * @method MapIcon find(int $id, array $columns = [])
 * @method MapIcon findOrFail(int $id, array $columns = [])
 * @method MapIcon findOrNew(int $id, array $columns = [])
 * @method bool save(MapIcon $model)
 * @method bool update(MapIcon $model, array $attributes = [], array $options = [])
 * @method bool delete(MapIcon $model)
 * @method Collection<MapIcon> all()
 */
interface MapIconRepositoryInterface extends BaseRepositoryInterface
{

}
