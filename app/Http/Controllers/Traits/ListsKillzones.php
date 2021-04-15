<?php
/**
 * Created by PhpStorm.
 * User: wouterk
 * Date: 18-2-2019
 * Time: 17:51
 */

namespace App\Http\Controllers\Traits;

use App\Models\DungeonRoute;
use App\Models\KillZone;
use Illuminate\Support\Collection;

trait ListsKillzones
{
    /**
     * Lists all killzones of a dungeon route.
     *
     * @param DungeonRoute|null $dungeonRoute
     * @return Collection
     */
    function listKillzones(?DungeonRoute $dungeonRoute = null): Collection
    {
        return KillZone::where('dungeon_route_id', $dungeonRoute->id)->get();
    }
}