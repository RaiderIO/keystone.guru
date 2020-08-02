<?php

namespace App\Http\Controllers;

use App\Events\Dungeon\ModelChangedEvent;
use App\Events\Dungeon\ModelDeletedEvent;
use App\Http\Controllers\Traits\ChecksForDuplicates;
use App\Http\Controllers\Traits\ListsDungeonFloorSwitchMarkers;
use App\Models\DungeonFloorSwitchMarker;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

        if ($dungeonFloorSwitchMarker->save()) {
            if (Auth::check()) {
                broadcast(new ModelChangedEvent($dungeonFloorSwitchMarker->floor->dungeon, $dungeonFloorSwitchMarker, Auth::getUser()));
            }
        } else {
            throw new \Exception('Unable to save dungeon floor switch marker!');
        }

        return ['id' => $dungeonFloorSwitchMarker->id];
    }

    /**
     * @param Request $request
     * @param DungeonFloorSwitchMarker $dungeonfloorswitchmarker
     * @return array|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    function delete(Request $request, DungeonFloorSwitchMarker $dungeonfloorswitchmarker)
    {
        try {
            $dungeon = $dungeonfloorswitchmarker->floor->dungeon;
            if( $dungeonfloorswitchmarker->delete() ) {
                if (Auth::check()) {
                    broadcast(new ModelDeletedEvent($dungeon, $dungeonfloorswitchmarker, Auth::getUser()));
                }
            }
            $result = response()->noContent();
        } catch (\Exception $ex) {
            $result = response('Not found', Http::NOT_FOUND);
        }

        return $result;
    }
}
