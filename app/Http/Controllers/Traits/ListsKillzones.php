<?php
/**
 * Created by PhpStorm.
 * User: wouterk
 * Date: 18-2-2019
 * Time: 17:51
 */

namespace App\Http\Controllers\Traits;

use App\Models\KillZone;
use App\Models\Path;
use Mockery\Exception;
use Teapot\StatusCode\Http;

trait ListsKillzones
{
    /**
     * Lists all killzones on a specific floor of a dungeon route.
     *
     * @param $floorId
     * @param $publicKey
     * @return Path[]
     */
    function listKillzones($floorId, $publicKey)
    {
        try {
            $dungeonRoute = $this->_getDungeonRouteFromPublicKey($publicKey, false);
            $result = KillZone::where('dungeon_route_id', $dungeonRoute->id)->where('floor_id', $floorId)->get();
        } catch (Exception $ex) {
            $result = response('Not found', Http::NOT_FOUND);
        }

        return $result;
    }
}