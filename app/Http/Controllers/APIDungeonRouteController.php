<?php

namespace App\Http\Controllers;

use App\Models\DungeonRoute;
use Illuminate\Http\Request;

class APIDungeonRouteController extends Controller
{
    function list(Request $request)
    {
        // @todo this must be the wrong way of doing it
        $result = datatables(DungeonRoute::with(['dungeon', 'affixes', 'author']))->toArray();
        unset($result['input']);
        unset($result['queries']);
        return json_encode($result);
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
