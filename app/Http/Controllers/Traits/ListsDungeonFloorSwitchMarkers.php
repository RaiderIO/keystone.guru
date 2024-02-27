<?php
/**
 * Created by PhpStorm.
 * User: wouterk
 * Date: 18-2-2019
 * Time: 17:42
 */

namespace App\Http\Controllers\Traits;

use App\Models\DungeonFloorSwitchMarker;
use Illuminate\Support\Collection;

trait ListsDungeonFloorSwitchMarkers
{
    /**
     * Lists all dungeon floor switch markers on a floor.
     */
    public function listDungeonFloorSwitchMarkers($floorId): Collection
    {
        return DungeonFloorSwitchMarker::where('floor_id', $floorId)->get();
    }
}
