<?php
/**
 * Created by PhpStorm.
 * User: wouterk
 * Date: 18-2-2019
 * Time: 17:51
 */

namespace App\Http\Controllers\Traits;

use App\Models\MapComment;
use Illuminate\Database\Query\Builder;

trait ListsMapComments
{
    /**
     * Lists all map comments of a floor.
     *
     * @param $floorId
     * @param $publicKey
     * @return MapComment[]
     */
    function listMapComments($floorId, $publicKey)
    {
        try {
            $dungeonRoute = $this->_getDungeonRouteFromPublicKey($publicKey, false);
            $dungeonRouteId = $dungeonRoute->id;
        } catch (\Exception $ex) {
            // this is okay, it can come from admin request
            $dungeonRouteId = -1;
        }

        return MapComment::where('floor_id', $floorId)
            ->where(function ($query) use ($floorId, $dungeonRouteId) {
                /** @var $query Builder */
                return $query->where('dungeon_route_id', $dungeonRouteId)->orWhere('always_visible', true);
            })->get();
    }
}