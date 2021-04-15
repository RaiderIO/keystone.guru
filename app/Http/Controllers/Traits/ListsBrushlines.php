<?php
/**
 * Created by PhpStorm.
 * User: wouterk
 * Date: 18-2-2019
 * Time: 17:51
 */

namespace App\Http\Controllers\Traits;

use App\Models\Brushline;
use App\Models\DungeonRoute;
use Illuminate\Support\Collection;
use Mockery\Exception;
use Teapot\StatusCode\Http;

trait ListsBrushlines
{
    /**
     * Lists all brushlines on a specific floor of a dungeon route.
     *
     * @param $floorId
     * @param DungeonRoute|null $dungeonRoute
     * @return Collection
     */
    function listBrushlines($floorId, ?DungeonRoute $dungeonRoute = null): Collection
    {
        try {
            $result = Brushline::with('polyline')
                ->where('dungeon_route_id', $dungeonRoute->id)
                ->where('floor_id', $floorId)
                ->get();
        } catch (Exception $ex) {
            $result = response('Not found', Http::NOT_FOUND);
        }

        return $result;
    }
}