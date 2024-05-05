<?php

namespace App\Repositories\Interfaces\DungeonRoute;

use App\Models\DungeonRoute\DungeonRoutePlayerRace;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method DungeonRoutePlayerRace create(array $attributes)
 * @method DungeonRoutePlayerRace find(int $id, array $columns = [])
 * @method DungeonRoutePlayerRace findOrFail(int $id, array $columns = [])
 * @method DungeonRoutePlayerRace findOrNew(int $id, array $columns = [])
 * @method bool save(DungeonRoutePlayerRace $model)
 * @method bool update(DungeonRoutePlayerRace $model, array $attributes = [], array $options = [])
 * @method bool delete(DungeonRoutePlayerRace $model)
 * @method Collection<DungeonRoutePlayerRace> all()
 */
interface DungeonRoutePlayerRaceRepositoryInterface extends BaseRepositoryInterface
{

}
