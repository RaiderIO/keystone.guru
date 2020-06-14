<?php

namespace App\Http\Controllers;

use App\Events\MapIconChangedEvent;
use App\Events\MapIconDeletedEvent;
use App\Http\Controllers\Traits\ChecksForDuplicates;
use App\Http\Controllers\Traits\ListsMapIcons;
use App\Http\Controllers\Traits\PublicKeyDungeonRoute;
use App\Models\DungeonRoute;
use App\Models\MapIcon;
use App\Models\MapIconType;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Teapot\StatusCode;
use Teapot\StatusCode\Http;

class APIMapIconController extends Controller
{
    use PublicKeyDungeonRoute;
    use ChecksForDuplicates;
    use ListsMapIcons;

    function list(Request $request)
    {
        return $this->listMapIcons(
            $request->get('floor_id'),
            $request->get('dungeonroute', null)
        );
    }

    /**
     * @param Request $request
     * @param DungeonRoute $dungeonroute
     * @return array
     * @throws \Exception
     */
    function store(Request $request, ?DungeonRoute $dungeonroute)
    {
        $isAdmin = Auth::check() && Auth::user()->hasRole('admin');
        // Must be an admin to use this endpoint like this!
        if ($dungeonroute === null) {
            if (!$isAdmin) {
                throw new \Exception('Unable to save map icon!');
            }
        } // We're editing a map comment for the user, carry on
        else if ($dungeonroute !== null && !$dungeonroute->isTry()) {
            $this->authorize('edit', $dungeonroute);
        }

        $mapIconTypeId = $request->get('map_icon_type_id', 0);

        if ($mapIconTypeId > 0) {
            /** @var MapIconType $mapIconType */
            $mapIconType = MapIconType::where('id', $mapIconTypeId)->first();

            // Only allow admins to save admin_only icons
            if ($mapIconType === null || $mapIconType->admin_only && !$isAdmin) {
                throw new \Exception('Unable to save map icon!');
            }
        }

        /** @var MapIcon $mapIcon */
        $mapIcon = MapIcon::findOrNew($request->get('id'));

        // Only admins may make global comments for all routes
        $mapIcon->floor_id = $request->get('floor_id');
        $mapIcon->dungeon_route_id = $dungeonroute === null ? -1 : $dungeonroute->id;
        $mapIcon->map_icon_type_id = $mapIconTypeId;
        $mapIcon->permanent_tooltip = $request->get('permanent_tooltip', false);
        $seasonalIndex = $request->get('seasonal_index');
        // don't use is_empty since 0 is valid
        $mapIcon->seasonal_index = $seasonalIndex === null || $seasonalIndex === '' ? null : $seasonalIndex;
        $mapIcon->comment = $request->get('comment', '') ?? '';
        $mapIcon->lat = $request->get('lat');
        $mapIcon->lng = $request->get('lng');

        if (!$mapIcon->exists) {
            $this->checkForDuplicate($mapIcon);
        }

        if (!$mapIcon->save()) {
            throw new \Exception('Unable to save map icon!');
        } else if ($dungeonroute !== null && Auth::check()) {
            broadcast(new MapIconChangedEvent($dungeonroute, $mapIcon, Auth::user()));
        }

        $result = ['id' => $mapIcon->id];

        return $result;
    }

    /**
     * @param Request $request
     * @param DungeonRoute $dungeonroute
     * @param MapIcon $mapicon
     * @return array|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     * @throws \Exception
     */
    function delete(Request $request, ?DungeonRoute $dungeonroute, MapIcon $mapicon)
    {
        $isAdmin = Auth::check() && Auth::user()->hasRole('admin');
        // Must be an admin to use this endpoint like this!
        if (!$isAdmin && ($dungeonroute === null || $mapicon->dungeon_route_id === -1)) {
            return response(null, StatusCode::FORBIDDEN);
        } // We're editing a map comment for the user, carry on
        else if ($dungeonroute !== null && !$dungeonroute->isTry()) {
            // Edit intentional; don't use delete rule because team members shouldn't be able to delete someone else's map comment
            $this->authorize('edit', $dungeonroute);
        }

        try {
            if ($mapicon->delete()) {
                if ($dungeonroute !== null && Auth::check()) {
                    broadcast(new MapIconDeletedEvent($dungeonroute, $mapicon, Auth::user()));
                }
                $result = ['result' => 'success'];
            } else {
                $result = ['result' => 'error'];
            }
        } catch (\Exception $ex) {
            $result = response('Not found', Http::NOT_FOUND);
        }

        return $result;
    }

    /**
     * @param Request $request
     * @return array|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     * @throws \Exception
     */
    function adminStore(Request $request)
    {
        return $this->store($request, null);
    }


    /**
     * @param Request $request
     * @param MapIcon $mapicon
     * @return array|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     * @throws \Exception
     */
    function adminDelete(Request $request, MapIcon $mapicon)
    {
        return $this->delete($request, null, $mapicon);
    }
}
