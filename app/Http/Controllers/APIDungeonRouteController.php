<?php

namespace App\Http\Controllers;

use App\Models\DungeonRoute;
use App\Models\DungeonRoutePlayerRace;
use App\Models\DungeonRoutePlayerClass;
use App\Models\DungeonRouteAffixGroup;
use Illuminate\Http\Request;

class APIDungeonRouteController extends Controller
{
    function list(Request $request)
    {
        $floorId = $request->get('floor_id');
        return DungeonRoute::all()->where('floor_id', '=', $floorId);
    }

    /**
     * @param Request $request
     * @param DungeonRoute $dungeonroute
     * @return array
     * @throws \Exception
     */
    function store(Request $request, DungeonRoute $dungeonroute = null)
    {
        if( $dungeonroute === null ){
            $dungeonroute = new DungeonRoute();
        }

        // Update or insert it
        if (!$dungeonroute->saveFromRequest($request)) {
            abort(500, 'Unable to save dungeonroute');
        }

        return ['id' => $dungeonroute->id];
    }
}
