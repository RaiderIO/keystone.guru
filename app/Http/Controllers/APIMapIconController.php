<?php

namespace App\Http\Controllers;

use App\Events\Model\ModelDeletedEvent;
use App\Http\Controllers\Traits\PublicKeyDungeonRoute;
use App\Http\Requests\MapIcon\MapIconFormRequest;
use App\Models\DungeonRoute;
use App\Models\MapIcon;
use App\Models\MapIconType;
use App\Models\Team;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Teapot\StatusCode;
use Teapot\StatusCode\Http;

class APIMapIconController extends APIMappingModelBaseController
{
    use PublicKeyDungeonRoute;

    /**
     * @param MapIconFormRequest $request
     * @param ?DungeonRoute $dungeonroute
     * @param MapIcon|null $mapIcon
     * @return MapIcon|Model
     * @throws AuthorizationException
     */
    public function store(MapIconFormRequest $request, ?DungeonRoute $dungeonroute, MapIcon $mapIcon = null): MapIcon
    {
        $validated = $request->validated();

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

        $mapIconTypeId = $validated['map_icon_type_id'];
        if ($mapIconTypeId !== null) {
            /** @var MapIconType $mapIconType */
            $mapIconType = MapIconType::where('id', $mapIconTypeId)->first();

            // Only allow admins to save admin_only icons
            if ($mapIconType === null || $mapIconType->admin_only && !$isUserAdmin) {
                throw new Exception('Unable to save map icon!');
            }
        }

        return $this->storeModel($validated, MapIcon::class, $mapIcon, function (MapIcon $mapIcon) use ($validated, $dungeonroute) {
            // Set the team_id if the user has the rights to do this. May be null if not set or no rights for it.
            $teamId = $validated['team_id'];
            if ($teamId !== null) {
                $team = Team::find($teamId);
                if ($team !== null && $team->isUserCollaborator(Auth::user())) {
                    $mapIcon->team_id          = $teamId;
                    $mapIcon->dungeon_route_id = null;
                    $mapIcon->save();
                }
            }

            // Set the mapping version if it was placed in the context of a dungeon
            if($dungeonroute === null){
                $mapIcon->mapping_version_id = $validated['mapping_version_id'];
                $mapIcon->save();
            }

            // Prevent people being able to update icons that only the admin should if they're supplying a valid dungeon route
            if ($mapIcon->exists && $mapIcon->dungeon_route_id === null && $dungeonroute !== null && $mapIcon->team_id === null) {
                throw new Exception('Unable to save map icon!');
            }

            // Set or unset the linked awakened obelisks now that we have an ID
            $mapIcon->setLinkedAwakenedObeliskByMapIconId($validated['linked_awakened_obelisk_id']);

            // Only when icons that are sticky to the map are saved
            if ($dungeonroute !== null) {
                $dungeonroute->touch();
            }
        });
    }

    /**
     * @param Request $request
     * @param DungeonRoute|null $dungeonroute
     * @param MapIcon $mapIcon
     * @return array|ResponseFactory|Response
     * @throws Exception
     */
    function delete(Request $request, ?DungeonRoute $dungeonroute, MapIcon $mapIcon)
    {
        $isAdmin = Auth::check() && Auth::user()->hasRole('admin');
        // Must be an admin to use this endpoint like this!
        if (!$isAdmin && ($dungeonroute === null || $mapIcon->dungeon_route_id === null)) {
            return response(null, StatusCode::FORBIDDEN);
        } // We're editing a map comment for the user, carry on
        else if ($dungeonroute !== null && !$dungeonroute->isSandbox()) {
            // Edit intentional; don't use delete rule because team members shouldn't be able to delete someone else's map comment
            $this->authorize('edit', $dungeonroute);
        }

        try {
            if ($mapIcon->delete()) {
                if (Auth::check()) {
                    broadcast(new ModelDeletedEvent($dungeonroute ?? $mapIcon->floor->dungeon, Auth::user(), $mapIcon));
                }

                // Only when icons that are sticky to the map are saved
                if ($dungeonroute === null) {
                    // Trigger mapping changed event so the mapping gets saved across all environments
                    $this->mappingChanged($mapIcon, null);
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
     * @param MapIconFormRequest $request
     * @param MapIcon|null $mapIcon
     * @return MapIcon
     * @throws AuthorizationException
     */
    public function adminStore(MapIconFormRequest $request, MapIcon $mapIcon = null): MapIcon
    {
        return $this->store($request, null, $mapIcon);
    }


    /**
     * @param Request $request
     * @param MapIcon $mapIcon
     * @return array|ResponseFactory|Response
     * @throws Exception
     */
    function adminDelete(Request $request, MapIcon $mapIcon)
    {
        return $this->delete($request, null, $mapIcon);
    }
}
