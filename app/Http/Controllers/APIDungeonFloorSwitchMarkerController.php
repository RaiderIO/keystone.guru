<?php

namespace App\Http\Controllers;

use App\Events\Model\ModelChangedEvent;
use App\Events\Model\ModelDeletedEvent;
use App\Http\Controllers\Traits\ChangesMapping;
use App\Http\Controllers\Traits\ListsDungeonFloorSwitchMarkers;
use App\Http\Requests\DungeonFloorSwitchMarker\DungeonFloorSwitchMarkerFormRequest;
use App\Models\DungeonFloorSwitchMarker;
use Exception;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Teapot\StatusCode\Http;
use Throwable;

class APIDungeonFloorSwitchMarkerController extends APIMappingModelBaseController
{
    use ListsDungeonFloorSwitchMarkers;

    public function list(Request $request)
    {
        return $this->listDungeonFloorSwitchMarkers($request->get('floor_id'));
    }

    /**
     * @param DungeonFloorSwitchMarkerFormRequest $request
     * @param DungeonFloorSwitchMarker $dungeonFloorSwitchMarker
     * @return DungeonFloorSwitchMarker|Model
     * @throws Throwable
     */
    public function store(DungeonFloorSwitchMarkerFormRequest $request, DungeonFloorSwitchMarker $dungeonFloorSwitchMarker = null): DungeonFloorSwitchMarker
    {
        $validated = $request->validated();

        return $this->storeModel($validated, DungeonFloorSwitchMarker::class, $dungeonFloorSwitchMarker);
    }

    /**
     * @param Request $request
     * @param DungeonFloorSwitchMarker $dungeonFloorSwitchMarker
     * @return ResponseFactory|Response
     */
    public function delete(Request $request, DungeonFloorSwitchMarker $dungeonFloorSwitchMarker)
    {
        try {
            $dungeon = $dungeonFloorSwitchMarker->floor->dungeon;
            if ($dungeonFloorSwitchMarker->delete()) {
                if (Auth::check()) {
                    broadcast(new ModelDeletedEvent($dungeon, Auth::getUser(), $dungeonFloorSwitchMarker));
                }

                // Trigger mapping changed event so the mapping gets saved across all environments
                $this->mappingChanged($dungeonFloorSwitchMarker, null);
            }
            $result = response()->noContent();
        } catch (Exception $ex) {
            $result = response('Not found', Http::NOT_FOUND);
        }

        return $result;
    }
}
