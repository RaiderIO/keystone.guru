<?php

namespace App\Http\Controllers;

use App\Events\Model\ModelChangedEvent;
use App\Events\Model\ModelDeletedEvent;
use App\Http\Controllers\Traits\ChangesMapping;
use App\Http\Controllers\Traits\ChecksForDuplicates;
use App\Http\Controllers\Traits\PublicKeyDungeonRoute;
use App\Models\DungeonRoute;
use App\Models\MapIcon;
use App\Models\MapIconType;
use App\Models\Team;
use Exception;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Teapot\StatusCode;
use Teapot\StatusCode\Http;

class APIMapIconController extends Controller
{
    use ChangesMapping;
    use PublicKeyDungeonRoute;
    use ChecksForDuplicates;

    /**
     * @param Request $request
     * @param ?DungeonRoute $dungeonroute
     * @return MapIcon
     * @throws Exception
     */
    function store(Request $request, ?DungeonRoute $dungeonroute)
    {
        $isUserAdmin = Auth::check() && Auth::user()->hasRole('admin');
        // Must be an admin to use this endpoint like this!
        if ($dungeonroute === null) {
            if (!$isUserAdmin) {
                throw new Exception('Unable to save map icon!');
            }
        } // We're editing a map comment for the user, carry on
        else if (!$dungeonroute->isSandbox()) {
            $this->authorize('edit', $dungeonroute);
        }

        $mapIconTypeId = (int)$request->get('map_icon_type_id', 0);

        if ($mapIconTypeId > 0) {
            /** @var MapIconType $mapIconType */
            $mapIconType = MapIconType::where('id', $mapIconTypeId)->first();

            // Only allow admins to save admin_only icons
            if ($mapIconType === null || $mapIconType->admin_only && !$isUserAdmin) {
                throw new Exception('Unable to save map icon!');
            }
        }

        /** @var MapIcon $mapIcon */
        $mapIcon = MapIcon::findOrNew($request->get('id'));

        $mapIconBefore = clone $mapIcon;

        // Set the team_id if the user has the rights to do this. May be null if not set or no rights for it.
        $teamId = $request->get('team_id', null);
        if (is_numeric($teamId)) {
            $team = Team::find($teamId);
            if ($team !== null && $team->isUserCollaborator(Auth::user())) {
                $mapIcon->team_id          = $teamId;
                $mapIcon->dungeon_route_id = -1;
            }
        }

        // Prevent people being able to update icons that only the admin should if they're supplying a valid dungeon route
        if ($mapIcon->exists && $mapIcon->dungeon_route_id === -1 && $dungeonroute !== null && $mapIcon->team_id === null) {
            throw new Exception('Unable to save map icon!');
        }

        // Only admins may make global comments for all routes
        $mapIcon->floor_id          = (int)$request->get('floor_id');
        $mapIcon->dungeon_route_id  = $dungeonroute === null ? -1 : $dungeonroute->id;
        $mapIcon->map_icon_type_id  = $mapIconTypeId;
        $mapIcon->permanent_tooltip = (int)$request->get('permanent_tooltip', false);
        $seasonalIndex              = $request->get('seasonal_index');
        // don't use empty() since 0 is valid
        $mapIcon->seasonal_index = $seasonalIndex === null || $seasonalIndex === '' ? null : (int)$seasonalIndex;
        $mapIcon->comment        = $request->get('comment', '') ?? '';
        $mapIcon->lat            = (float)$request->get('lat');
        $mapIcon->lng            = (float)$request->get('lng');

        if ($mapIcon->save()) {
            // Set or unset the linked awakened obelisks now that we have an ID
            $mapIcon->setLinkedAwakenedObeliskByMapIconId($request->get('linked_awakened_obelisk_id', null));

            if (Auth::check()) {
                broadcast(new ModelChangedEvent($dungeonroute ?? $mapIcon->floor->dungeon, Auth::user(), $mapIcon));
            }

            // Only when icons that are sticky to the map are saved
            if ($dungeonroute === null) {
                // Trigger mapping changed event so the mapping gets saved across all environments
                $this->mappingChanged($mapIconBefore, $mapIcon);
            } else {
                $dungeonroute->touch();
            }
        } else {
            throw new Exception('Unable to save map icon!');
        }

        return $mapIcon;
    }

    /**
     * @param Request $request
     * @param DungeonRoute|null $dungeonroute
     * @param MapIcon $mapicon
     * @return array|ResponseFactory|Response
     * @throws Exception
     */
    function delete(Request $request, ?DungeonRoute $dungeonroute, MapIcon $mapicon)
    {
        $isAdmin = Auth::check() && Auth::user()->hasRole('admin');
        // Must be an admin to use this endpoint like this!
        if (!$isAdmin && ($dungeonroute === null || $mapicon->dungeon_route_id === -1)) {
            return response(null, StatusCode::FORBIDDEN);
        } // We're editing a map comment for the user, carry on
        else if ($dungeonroute !== null && !$dungeonroute->isSandbox()) {
            // Edit intentional; don't use delete rule because team members shouldn't be able to delete someone else's map comment
            $this->authorize('edit', $dungeonroute);
        }

        try {
            if ($mapicon->delete()) {
                if (Auth::check()) {
                    broadcast(new ModelDeletedEvent($dungeonroute ?? $mapicon->floor->dungeon, Auth::user(), $mapicon));
                }

                // Only when icons that are sticky to the map are saved
                if ($dungeonroute === null) {
                    // Trigger mapping changed event so the mapping gets saved across all environments
                    $this->mappingChanged($mapicon, null);
                } else {
                    $dungeonroute->touch();
                }

                $result = response()->noContent();
            } else {
                $result = ['result' => 'error'];
            }
        } catch (Exception $ex) {
            $result = response('Not found', Http::NOT_FOUND);
        }

        return $result;
    }

    /**
     * @param Request $request
     * @return array|ResponseFactory|Response
     * @throws Exception
     */
    function adminStore(Request $request)
    {
        return $this->store($request, null);
    }


    /**
     * @param Request $request
     * @param MapIcon $mapicon
     * @return array|ResponseFactory|Response
     * @throws Exception
     */
    function adminDelete(Request $request, MapIcon $mapicon)
    {
        return $this->delete($request, null, $mapicon);
    }
}
