<?php

namespace App\Repositories\Interfaces;

use App\Models\DungeonFloorSwitchMarker;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method DungeonFloorSwitchMarker create(array $attributes)
 * @method DungeonFloorSwitchMarker find(int $id, array $columns = [])
 * @method DungeonFloorSwitchMarker findOrFail(int $id, array $columns = [])
 * @method DungeonFloorSwitchMarker findOrNew(int $id, array $columns = [])
 * @method bool save(DungeonFloorSwitchMarker $model)
 * @method bool update(DungeonFloorSwitchMarker $model, array $attributes = [], array $options = [])
 * @method bool delete(DungeonFloorSwitchMarker $model)
 * @method Collection<DungeonFloorSwitchMarker> all()
 */
interface DungeonFloorSwitchMarkerRepositoryInterface extends BaseRepositoryInterface
{

}
