<?php
/**
 * Created by PhpStorm.
 * User: wouterk
 * Date: 18-2-2019
 * Time: 17:42
 */

namespace App\Http\Controllers\Traits;

use App\Models\DungeonFloorSwitchMarker;

trait ListsDungeonFloorSwitchMarkers
{

    /**
     * Lists all dungeon floor switch markers on a floor.
     *
     * @param $floorId
     * @return DungeonFloorSwitchMarker[]
     */
    function listDungeonFloorSwitchMarkers($floorId)
    {
        return DungeonFloorSwitchMarker::all()->where('floor_id', $floorId);
    }
}