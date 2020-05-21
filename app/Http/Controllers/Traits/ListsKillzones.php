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
use Illuminate\Support\Collection;
use Mockery\Exception;
use Teapot\StatusCode\Http;

trait ListsKillzones
{
    /**
     * Lists all killzones of a dungeon route.
     *
     * @param $publicKey
     * @return Collection
     */
    function listKillzones($publicKey)
    {
        try {
            $dungeonRoute = $this->_getDungeonRouteFromPublicKey($publicKey, false);
            $result = KillZone::where('dungeon_route_id', $dungeonRoute->id)->get();
        } catch (Exception $ex) {
            $result = response('Not found', Http::NOT_FOUND);
        }

        return $result;
    }
}