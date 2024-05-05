<?php

namespace App\Repositories\Interfaces\DungeonRoute;

use App\Models\DungeonRoute\DungeonRouteEnemyRaidMarker;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method DungeonRouteEnemyRaidMarker create(array $attributes)
 * @method DungeonRouteEnemyRaidMarker find(int $id, array $columns = [])
 * @method DungeonRouteEnemyRaidMarker findOrFail(int $id, array $columns = [])
 * @method DungeonRouteEnemyRaidMarker findOrNew(int $id, array $columns = [])
 * @method bool save(DungeonRouteEnemyRaidMarker $model)
 * @method bool update(DungeonRouteEnemyRaidMarker $model, array $attributes = [], array $options = [])
 * @method bool delete(DungeonRouteEnemyRaidMarker $model)
 * @method Collection<DungeonRouteEnemyRaidMarker> all()
 */
interface DungeonRouteEnemyRaidMarkerRepositoryInterface extends BaseRepositoryInterface
{

}
