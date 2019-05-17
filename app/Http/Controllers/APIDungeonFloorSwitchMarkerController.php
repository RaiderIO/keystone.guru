<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\ChecksForDuplicates;
use App\Http\Controllers\Traits\ListsDungeonFloorSwitchMarkers;
use App\Models\DungeonFloorSwitchMarker;
use Illuminate\Http\Request;
use Teapot\StatusCode\Http;

class APIDungeonFloorSwitchMarkerController extends Controller
{
    use ChecksForDuplicates;
    use ListsDungeonFloorSwitchMarkers;

    function list(Request $request)
    {
        return $this->listDungeonFloorSwitchMarkers($request->get('floor_id'));
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

        // Find out of there is a duplicate
        if (!$dungeonFloorSwitchMarker->exists) {
            $this->checkForDuplicate($dungeonFloorSwitchMarker);
        }

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
