<?php

namespace App\Http\Controllers;

use App\Models\DungeonRoute;
use App\Models\DungeonRoutePlayerRace;
use App\Models\DungeonRoutePlayerClass;
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
     * @param int $id
     * @return array
     * @throws \Exception
     */
    function store(Request $request, int $id)
    {
        /** @var DungeonRoute $dungeonroute */
        $dungeonroute = DungeonRoute::findOrNew($id);

        if (!isset($dungeonroute->id)) {
            $dungeonroute->author_id = \Auth::user()->id;
        }
        $dungeonroute->dungeon_id = $request->get('dungeon', $dungeonroute->dungeon_id);
        $dungeonroute->faction_id = $request->get('faction', $dungeonroute->faction_id);
        $dungeonroute->title = $request->get('title', $dungeonroute->title);

        // Update or insert it
        if ($dungeonroute->save()) {
            $newRaces = $request->get('race', array());

            if (!empty($newRaces)) {
                // Remove old races
                $dungeonroute->races()->delete();

                // We don't _really_ care if this doesn't get saved properly, they can just set it again when editing.
                foreach ($newRaces as $key => $value) {
                    $drpRace = new DungeonRoutePlayerRace();
                    $drpRace->index = $key;
                    $drpRace->race_id = $value;
                    $drpRace->dungeon_route_id = $dungeonroute->id;
                    $drpRace->save();
                }
            }

            $newClasses = $request->get('class', array());
            if (!empty($newClasses)) {
                // Remove old classes
                $dungeonroute->classes()->delete();
                foreach ($newClasses as $key => $value) {
                    $drpClass = new DungeonRoutePlayerClass();
                    $drpClass->index = $key;
                    $drpClass->class_id = $value;
                    $drpClass->dungeon_route_id = $dungeonroute->id;
                    $drpClass->save();
                }
            }

            $newAffixes = $request->get('affixes', array());
            if (!empty($newAffixes)) {
                // Remove old affixes
                $dungeonroute->classes()->delete();
                foreach ($newClasses as $key => $value) {
                    $drpClass = new DungeonRoutePlayerClass();
                    $drpClass->index = $key;
                    $drpClass->class_id = $value;
                    $drpClass->dungeon_route_id = $dungeonroute->id;
                    $drpClass->save();
                }
            }
        } else {
            abort(500, 'Unable to save dungeonroute');
        }

        return ['id' => $dungeonroute->id];
    }
}
