<?php
/**
 * Created by PhpStorm.
 * User: wouterk
 * Date: 18-2-2019
 * Time: 17:42
 */

namespace App\Http\Controllers\Traits;

use App\Models\DungeonFloorSwitchMarker;
use App\Models\DungeonStartMarker;

trait ListsDungeonStartMarkers
{

    /**
     * Lists all dungeon start markers on a floor.
     *
     * @param $floorId
     * @return DungeonStartMarker[]
     */
    function listDungeonStartMarkers($floorId)
    {
        return DungeonStartMarker::all()->where('floor_id', $floorId);
    }
}