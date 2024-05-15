<?php

namespace App\Repositories\Interfaces\DungeonRoute;

use App\Models\DungeonRoute\DungeonRouteEnemyRaidMarker;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method DungeonRouteEnemyRaidMarker create(array $attributes)
 * @method DungeonRouteEnemyRaidMarker|null find(int $id, array|string $columns = ['*'])
 * @method DungeonRouteEnemyRaidMarker findOrFail(int $id, array|string $columns = ['*'])
 * @method DungeonRouteEnemyRaidMarker findOrNew(int $id, array|string $columns = ['*'])
 * @method bool save(DungeonRouteEnemyRaidMarker $model)
 * @method bool update(DungeonRouteEnemyRaidMarker $model, array $attributes = [], array $options = [])
 * @method bool delete(DungeonRouteEnemyRaidMarker $model)
 * @method Collection<DungeonRouteEnemyRaidMarker> all()
 */
interface DungeonRouteEnemyRaidMarkerRepositoryInterface extends BaseRepositoryInterface
{

}
