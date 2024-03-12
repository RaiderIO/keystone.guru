<?php

namespace App\Http\Controllers\Ajax;

use App\Events\Model\ModelDeletedEvent;
use App\Http\Controllers\Traits\ListsDungeonFloorSwitchMarkers;
use App\Http\Requests\DungeonFloorSwitchMarker\DungeonFloorSwitchMarkerFormRequest;
use App\Models\DungeonFloorSwitchMarker;
use App\Models\Mapping\MappingVersion;
use App\Models\User;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Teapot\StatusCode\Http;
use Throwable;

class AjaxDungeonFloorSwitchMarkerController extends AjaxMappingModelBaseController
{
    use ListsDungeonFloorSwitchMarkers;

    public function get(Request $request): Collection
    {
        return $this->listDungeonFloorSwitchMarkers($request->get('floor_id'));
    }

    /**
     * @throws Throwable
     */
    public function store(
        DungeonFloorSwitchMarkerFormRequest $request,
        MappingVersion                      $mappingVersion,
        ?DungeonFloorSwitchMarker           $dungeonFloorSwitchMarker = null
    ): DungeonFloorSwitchMarker|Model {
        return $this->storeModel($mappingVersion, $request->validated(), DungeonFloorSwitchMarker::class, $dungeonFloorSwitchMarker);
    }

    public function delete(
        Request                  $request,
        MappingVersion           $mappingVersion,
        DungeonFloorSwitchMarker $dungeonFloorSwitchMarker
    ): Response {
        try {
            $dungeon = $dungeonFloorSwitchMarker->floor->dungeon;
            if ($dungeonFloorSwitchMarker->delete()) {
                if (Auth::check()) {
                    /** @var User $user */
                    $user = Auth::getUser();
                    broadcast(new ModelDeletedEvent($dungeon, $user, $dungeonFloorSwitchMarker));
                }

                // Trigger mapping changed event so the mapping gets saved across all environments
                $this->mappingChanged($dungeonFloorSwitchMarker, null);
            }

            $result = response()->noContent();
        } catch (Exception) {
            $result = response(__('controller.generic.error.not_found'), Http::NOT_FOUND);
        }

        return $result;
    }
}
