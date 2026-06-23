<?php

namespace App\Repositories\Interfaces\DungeonRoute;

use App\Models\DungeonRoute\DungeonRouteEnemyRaidMarker;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method DungeonRouteEnemyRaidMarker                  create(array<string, mixed> $attributes)
 * @method DungeonRouteEnemyRaidMarker|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method DungeonRouteEnemyRaidMarker                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method DungeonRouteEnemyRaidMarker                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                                         save(DungeonRouteEnemyRaidMarker $model)
 * @method bool                                         update(DungeonRouteEnemyRaidMarker $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                                         delete(DungeonRouteEnemyRaidMarker $model)
 * @method Collection<int, DungeonRouteEnemyRaidMarker> all()
 * @method bool                                         exists(array<int, string> $columns)
 */
interface DungeonRouteEnemyRaidMarkerRepositoryInterface extends BaseRepositoryInterface
{
}
