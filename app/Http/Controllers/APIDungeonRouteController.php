<?php

namespace App\Http\Controllers;

use App\Models\DungeonRoute;
use App\Models\DungeonRoutePlayerRace;
use App\Models\DungeonRoutePlayerClass;
use Illuminate\Http\Request;

class APIDungeonRouteController extends Controller
{
    /**
     * @param Request $request
     * @param int $id
     * @return array
     * @throws \Exception
     */
    function store(Request $request, int $id)
    {
        /** @var DungeonRoute $dungeonroute */
        $dungeonroute = DungeonRoute::findOrNew($id);

        $dungeonroute->dungeon_id = $request->get('dungeon', $dungeonroute->dungeon_id);
        $dungeonroute->faction = $request->get('faction', $dungeonroute->faction);

        // Update or insert it
        if ($dungeonroute->save()) {
            // Remove old races
            $dungeonroute->races()->delete();

            // We don't _really_ care if this doesn't get saved properly, they can just set it again when editing.
            foreach($request->get('race') as $key => $value){
                $drpRace = new DungeonRoutePlayerRace();
                $drpRace->index = $key;
                $drpRace->race_id = $value;
                $drpRace->dungeon_route_id = $dungeonroute->id;
                $drpRace->save();
            }

            // Remove old classes
            $dungeonroute->classes()->delete();
            foreach($request->get('class') as $key => $value){
                $drpClass = new DungeonRoutePlayerClass();
                $drpClass->index = $key;
                $drpClass->class_id = $value;
                $drpClass->dungeon_route_id = $dungeonroute->id;
                $drpClass->save();
            }
        } else {
            abort(500, 'Unable to save dungeonroute');
        }

        return ['id' => $dungeonroute->id];
    }
}
