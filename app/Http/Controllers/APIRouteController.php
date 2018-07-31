<?php

namespace App\Http\Controllers;

use App\Models\DungeonRoute;
use Illuminate\Http\Request;
use App\Models\Route;
use Teapot\StatusCode\Http;

class APIRouteController extends Controller
{
    /**
     * @param Request $request
     * @return array
     * @throws \Exception
     */
    function store(Request $request)
    {
        /** @var Enemy $enemy */
        $enemy = Enemy::findOrNew($request->get('id'));

        $enemy->enemy_pack_id = $request->get('enemy_pack_id');
        $enemy->npc_id = $request->get('npc_id');
        $enemy->floor_id = $request->get('floor_id');
        $enemy->lat = $request->get('lat');
        $enemy->lng = $request->get('lng');

        if (!$enemy->save()) {
            throw new \Exception("Unable to save enemy!");
        }

        return ['id' => $enemy->id];
    }

    function get(Request $request)
    {
        /** @var DungeonRoute $dungeonroute */
        $dungeonroute = DungeonRoute::findOrFail($request->get('dungeonroute'));

        /** @var Route $route */
        $route = Route::findOrFail($dungeonroute->id);

        return $route;
    }
}
