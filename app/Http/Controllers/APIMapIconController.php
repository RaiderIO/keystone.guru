<?php

namespace App\Http\Controllers;

use App\Events\Model\ModelDeletedEvent;
use App\Http\Controllers\Traits\PublicKeyDungeonRoute;
use App\Http\Requests\MapIcon\MapIconFormRequest;
use App\Models\DungeonRoute;
use App\Models\MapIcon;
use App\Models\MapIconType;
use App\Models\Mapping\MappingModelInterface;
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
use Throwable;

class APIMapIconController extends APIMappingModelBaseController
{
    use PublicKeyDungeonRoute;


    protected function shouldCallMappingChanged(?MappingModelInterface $beforeModel, ?MappingModelInterface $afterModel): bool
    {
        /** @var MapIcon $beforeModel */
        /** @var MapIcon $afterModel */
        return optional($beforeModel)->dungeon_route_id === null || optional($afterModel)->dungeon_route_id === null;
    }

    /**
     * @param MapIconFormRequest $request
     * @param ?DungeonRoute $dungeonRoute
     * @param MapIcon|null $mapIcon
     * @return MapIcon|Model
     * @throws AuthorizationException
     * @throws Throwable
     */
    public function store(MapIconFormRequest $request, ?DungeonRoute $dungeonRoute, MapIcon $mapIcon = null): MapIcon
    {
        $dungeonRoute                  = optional($mapIcon)->dungeonRoute ?? $dungeonRoute;
        $validated                     = $request->validated();
        $validated['dungeon_route_id'] = optional($dungeonRoute)->id;

        $isUserAdmin = Auth::check() && Auth::user()->hasRole('admin');
        // Must be an admin to use this endpoint like this!
        if ($dungeonRoute === null) {
            if (!$isUserAdmin) {
                throw new Exception('Unable to save map icon!');
            }
        } // We're editing a map comment for the user, carry on
        else if (!$dungeonRoute->isSandbox()) {
            $this->authorize('edit', $dungeonRoute);
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

        dd('OK to store!');

        return $this->storeModel($validated, MapIcon::class, $mapIcon, function (MapIcon $mapIcon) use ($validated, $dungeonRoute) {
            // Set the team_id if the user has the rights to do this. May be null if not set or no rights for it.
            $teamId = $validated['team_id'];
            if ($teamId !== null) {
                $team = Team::find($teamId);
                if ($team !== null && $team->isUserCollaborator(Auth::user())) {
                    $mapIcon->update([
                        'team_id'          => $teamId,
                        'dungeon_route_id' => null,
                    ]);
                }
            }

            // Set the mapping version if it was placed in the context of a dungeon, or reset it to null if not in context
            // of a dungeon
            $mapIcon->update([
                'mapping_version_id' => $dungeonRoute === null ? $validated['mapping_version_id'] : null,
            ]);

            // Prevent people being able to update icons that only the admin should if they're supplying a valid dungeon route
            if ($mapIcon->exists && $mapIcon->dungeon_route_id === null && $dungeonRoute !== null && $mapIcon->team_id === null) {
                throw new Exception('Unable to save map icon!');
            }

            // Set or unset the linked awakened obelisks now that we have an ID
            $mapIcon->setLinkedAwakenedObeliskByMapIconId($validated['linked_awakened_obelisk_id']);

            // Only when icons that are not sticky to the map are saved
            if ($dungeonRoute !== null) {
                $dungeonRoute->touch();
            }
        });
    }

    /**
     * @param Request $request
     * @param DungeonRoute|null $dungeonRoute
     * @param MapIcon $mapIcon
     * @return array|ResponseFactory|Response
     * @throws Exception
     */
    function delete(Request $request, ?DungeonRoute $dungeonRoute, MapIcon $mapIcon)
    {
        $isAdmin = Auth::check() && Auth::user()->hasRole('admin');
        // Must be an admin to use this endpoint like this!
        if (!$isAdmin && ($dungeonRoute === null || $mapIcon->dungeon_route_id === null)) {
            return response(null, StatusCode::FORBIDDEN);
        } // We're editing a map comment for the user, carry on
        else if ($dungeonRoute !== null && !$dungeonRoute->isSandbox()) {
            // Edit intentional; don't use delete rule because team members shouldn't be able to delete someone else's map comment
            $this->authorize('edit', $dungeonRoute);
        }

        try {
            if ($mapIcon->delete()) {
                if (Auth::check()) {
                    broadcast(new ModelDeletedEvent($dungeonRoute ?? $mapIcon->floor->dungeon, Auth::user(), $mapIcon));
                }

                // Only when icons that are sticky to the map are saved
                if ($dungeonRoute === null) {
                    // Trigger mapping changed event so the mapping gets saved across all environments
                    $this->mappingChanged($mapIcon, null);
                } else {
                    $dungeonRoute->touch();
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
     * @throws Throwable
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
