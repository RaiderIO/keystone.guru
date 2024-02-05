<?php
/**
 * Created by PhpStorm.
 * User: wouterk
 * Date: 18-2-2019
 * Time: 17:51
 */

namespace App\Http\Controllers\Traits;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\MapIcon;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;

trait ListsMapIcons
{
    /**
     * Lists all map icons of a floor.
     *
     * @param $floorId
     * @param DungeonRoute|null $dungeonRoute
     * @return MapIcon[]|Collection
     */
    function listMapIcons($floorId, ?DungeonRoute $dungeonRoute)
    {
        try {
            $dungeonRouteId = $dungeonRoute->id;
            $teamId         = $dungeonRoute->team_id;
        } catch (\Exception $ex) {
            // this is okay, it can come from admin request
            $dungeonRouteId = null;
            $teamId         = -1;
        }

        return MapIcon::where('floor_id', $floorId)
            ->where(function ($query) use ($floorId, $dungeonRouteId, $teamId) {
                /** @var $query Builder */
                return $query->where('dungeon_route_id', $dungeonRouteId)->orWhereNull('dungeon_route_id')->orWhere('team_id', $teamId);
            })
            // Order by dungeon route so that route-agnostic icons are loaded first in the front end, and the linked map icons can always find them
            ->orderBy('dungeon_route_id')->get();
    }
}
