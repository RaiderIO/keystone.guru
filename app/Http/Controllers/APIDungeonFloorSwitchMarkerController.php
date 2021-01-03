<?php

namespace App\Http\Controllers;

use App\Events\ModelChangedEvent;
use App\Events\ModelDeletedEvent;
use App\Http\Controllers\Traits\ChangesMapping;
use App\Http\Controllers\Traits\ChecksForDuplicates;
use App\Http\Controllers\Traits\ListsDungeonFloorSwitchMarkers;
use App\Models\DungeonFloorSwitchMarker;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Teapot\StatusCode\Http;

class APIDungeonFloorSwitchMarkerController extends Controller
{
    use ChangesMapping;
    use ChecksForDuplicates;
    use ListsDungeonFloorSwitchMarkers;

    function list(Request $request)
    {
        return $this->listDungeonFloorSwitchMarkers($request->get('floor_id'));
    }

    /**
     * @param Request $request
     * @return DungeonFloorSwitchMarker
     * @throws \Exception
     */
    function store(Request $request)
    {
        /** @var DungeonFloorSwitchMarker $dungeonFloorSwitchMarker */
        $dungeonFloorSwitchMarker = DungeonFloorSwitchMarker::findOrNew($request->get('id'));

        $dungeonFloorSwitchMarkerBefore = clone $dungeonFloorSwitchMarker;

        $dungeonFloorSwitchMarker->floor_id = (int) $request->get('floor_id');
        $dungeonFloorSwitchMarker->target_floor_id = (int) $request->get('target_floor_id');
        $dungeonFloorSwitchMarker->lat = (float) $request->get('lat');
        $dungeonFloorSwitchMarker->lng = (float) $request->get('lng');

        // Find out of there is a duplicate
        if (!$dungeonFloorSwitchMarker->exists) {
            $this->checkForDuplicate($dungeonFloorSwitchMarker, ['floor_id']);
        }

        if ($dungeonFloorSwitchMarker->save()) {
            if (Auth::check()) {
                broadcast(new ModelChangedEvent($dungeonFloorSwitchMarker->floor->dungeon, Auth::getUser(), $dungeonFloorSwitchMarker));
            }

            // Trigger mapping changed event so the mapping gets saved across all environments
            $this->mappingChanged($dungeonFloorSwitchMarkerBefore, $dungeonFloorSwitchMarker);
        } else {
            throw new \Exception('Unable to save dungeon floor switch marker!');
        }

        return $dungeonFloorSwitchMarker;
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
                    broadcast(new ModelDeletedEvent($dungeon, Auth::getUser(), $dungeonfloorswitchmarker));
                }

                // Trigger mapping changed event so the mapping gets saved across all environments
                $this->mappingChanged($dungeonfloorswitchmarker, null);
            }
            $result = response()->noContent();
        } catch (\Exception $ex) {
            $result = response('Not found', Http::NOT_FOUND);
        }

        return $result;
    }
}
