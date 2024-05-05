<?php

namespace App\Repositories\Interfaces\DungeonRoute;

use App\Models\DungeonRoute\DungeonRouteAttribute;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method DungeonRouteAttribute create(array $attributes)
 * @method DungeonRouteAttribute find(int $id, array $columns = [])
 * @method DungeonRouteAttribute findOrFail(int $id, array $columns = [])
 * @method DungeonRouteAttribute findOrNew(int $id, array $columns = [])
 * @method bool save(DungeonRouteAttribute $model)
 * @method bool update(DungeonRouteAttribute $model, array $attributes = [], array $options = [])
 * @method bool delete(DungeonRouteAttribute $model)
 * @method Collection<DungeonRouteAttribute> all()
 */
interface DungeonRouteAttributeRepositoryInterface extends BaseRepositoryInterface
{

}
