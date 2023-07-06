<?php
/**
 * Created by PhpStorm.
 * User: wouterk
 * Date: 18-2-2019
 * Time: 17:51
 */

namespace App\Http\Controllers\Traits;

use App\Models\DungeonRoute;
use App\Models\Path;
use Illuminate\Support\Collection;

trait ListsPaths
{
    /**
     * Lists all paths on a specific floor of a dungeon route.
     *
     * @param $floorId
     * @param DungeonRoute|null $dungeonRoute
     * @return Collection
     */
    function listPaths($floorId, ?DungeonRoute $dungeonRoute): Collection
    {
        return Path::with('polyline')
            ->where('dungeon_route_id', $dungeonRoute->id)
            ->where('floor_id', $floorId)
            ->get();
    }
}