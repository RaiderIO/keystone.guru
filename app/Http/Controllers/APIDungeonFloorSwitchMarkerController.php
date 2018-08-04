<?php

namespace App\Http\Controllers;

use App\Models\DungeonFloorSwitchMarker;
use Illuminate\Http\Request;
use Teapot\StatusCode\Http;

class APIDungeonFloorSwitchMarkerController extends Controller
{
    function list(Request $request)
    {
        $floorId = $request->get('floor_id');
        return DungeonFloorSwitchMarker::all()->where('floor_id', '=', $floorId);
    }

    /**
     * @param Request $request
     * @return array
     * @throws \Exception
     */
    function store(Request $request)
    {
        /** @var DungeonFloorSwitchMarker $dungeonFloorSwitchMarker */
        $dungeonFloorSwitchMarker = DungeonFloorSwitchMarker::findOrNew($request->get('id'));

        $dungeonFloorSwitchMarker->floor_id = $request->get('floor_id');
        $dungeonFloorSwitchMarker->target_floor_id = $request->get('target_floor_id');
        $dungeonFloorSwitchMarker->lat = $request->get('lat');
        $dungeonFloorSwitchMarker->lng = $request->get('lng');

        if (!$dungeonFloorSwitchMarker->save()) {
            throw new \Exception("Unable to save dungeon start marker!");
        }

        return ['id' => $dungeonFloorSwitchMarker->id];
    }

    function delete(Request $request)
    {
        try {
            /** @var DungeonFloorSwitchMarker $dungeonStartMarker */
            $dungeonStartMarker = DungeonFloorSwitchMarker::findOrFail($request->get('id'));

            $dungeonStartMarker->delete();
            $result = ['result' => 'success'];
        } catch (\Exception $ex) {
            $result = response('Not found', Http::NOT_FOUND);
        }

        return $result;
    }
}
