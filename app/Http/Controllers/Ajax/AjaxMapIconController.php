<?php

namespace App\Http\Controllers\Ajax;

use App\Events\Models\MapIcon\MapIconChangedEvent;
use App\Events\Models\MapIcon\MapIconDeletedEvent;
use App\Events\Models\ModelChangedEvent;
use App\Http\Controllers\Traits\ChangesDungeonRoute;
use App\Http\Requests\MapIcon\MapIconFormRequest;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Laratrust\Role;
use App\Models\MapIcon;
use App\Models\Mapping\MappingModelInterface;
use App\Models\Mapping\MappingVersion;
use App\Models\Team;
use App\Models\User;
use App\Service\Coordinates\CoordinatesServiceInterface;
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

class AjaxMapIconController extends AjaxMappingModelBaseController
{
    use ChangesDungeonRoute;

    protected function shouldCallMappingChanged(?MappingModelInterface $beforeModel, ?MappingModelInterface $afterModel): bool
    {
        /** @var MapIcon $beforeModel */
        /** @var MapIcon $afterModel */
        return $beforeModel?->dungeon_route_id === null || $afterModel?->dungeon_route_id === null;
    }

    /**
     * @param CoordinatesServiceInterface $coordinatesService
     * @param MapIconFormRequest          $request
     * @param MappingVersion|null         $mappingVersion Set -> admin endpoint,
     * @param DungeonRoute|null           $dungeonRoute Set -> route edit endpoint
     * @param MapIcon|null                $mapIcon
     * @return MapIcon|Model
     *
     * @throws AuthorizationException
     * @throws Throwable
     */
    public function store(
        CoordinatesServiceInterface $coordinatesService,
        MapIconFormRequest          $request,
        ?MappingVersion             $mappingVersion,
        ?DungeonRoute               $dungeonRoute,
        ?MapIcon                    $mapIcon = null
    ): MapIcon {
        $dungeonRoute                  = $mapIcon?->dungeonRoute ?? $dungeonRoute;
        $validated                     = $request->validated();
        $validated['dungeon_route_id'] = $dungeonRoute?->id;

        /** @var User|null $user */
        $user = Auth::user();

        $isUserAdmin = $user?->hasRole(Role::ROLE_ADMIN);
        // Must be an admin to use this endpoint like this!
        if ($dungeonRoute === null) {
            if (!$isUserAdmin) {
                throw new Exception('Unable to save map icon!');
            }
        } // We're editing a map comment for the user, carry on
        else {
            $this->authorize('edit', $dungeonRoute);
            $this->authorize('addMapIcon', $dungeonRoute);
        }

        $beforeModel = $mapIcon === null ? null : clone $mapIcon;

        return $this->storeModel($coordinatesService, $mappingVersion, $validated, MapIcon::class, $mapIcon,
            function (MapIcon $mapIcon) use ($coordinatesService, $validated, $user, $dungeonRoute, &$beforeModel) {
                // Set the team_id if the user has the rights to do this. May be null if not set or no rights for it.
                $updateAttributes = [];
                $teamId           = $validated['team_id'];
                if ($teamId !== null) {
                    $team = Team::find($teamId);
                    if ($team !== null && $user !== null && $team->isUserCollaborator($user)) {
                        $updateAttributes = [
                            'team_id'          => $teamId,
                            'dungeon_route_id' => null,
                        ];
                    }
                }

                // Prevent people being able to update icons that only the admin should if they're supplying a valid dungeon route
                if ($mapIcon->exists && $mapIcon->dungeon_route_id === null && $dungeonRoute !== null && $mapIcon->team_id === null) {
                    throw new Exception('Unable to save map icon!');
                }

                // The incoming lat/lngs are facade lat/lngs, save the icon on the proper floor
                // If we're editing from an admin PoV facade is NEVER enabled, so ignore this then!
                $useFacade = $dungeonRoute?->mappingVersion->facade_enabled &&
                    User::getCurrentUserMapFacadeStyle() === User::MAP_FACADE_STYLE_FACADE;

                // Track this latlng so we can re-echo it back to the user if we still want to use facades
                $originalLatLng = $mapIcon->getLatLng();
                if ($useFacade) {
                    $latLng = $coordinatesService->convertFacadeMapLocationToMapLocation(
                        $dungeonRoute->mappingVersion,
                        $originalLatLng
                    );

                    $updateAttributes = array_merge($updateAttributes, [
                        'lat'      => $latLng->getLat(),
                        'lng'      => $latLng->getLng(),
                        'floor_id' => $latLng->getFloor()->id,
                    ]);

                    $mapIcon->setRelation('floor', $latLng->getFloor());
                    // Ensure the dungeon is loaded (required for the base class)
                    $mapIcon->load(['floor.dungeon']);
                }

                // Set the mapping version if it was placed in the context of a dungeon, or reset it to null if not in context
                // of a dungeon
                $mapIcon->update(array_merge($updateAttributes, [
                    'mapping_version_id' => $dungeonRoute === null ? $validated['mapping_version_id'] : null,
                ]));

                // Set or unset the linked awakened obelisks now that we have an ID
                $mapIcon->setLinkedAwakenedObeliskByMapIconId($validated['linked_awakened_obelisk_id']);

                // Only when icons that are not sticky to the map are saved
                $dungeonRoute?->touch();

                if ($dungeonRoute !== null) {
                    $this->dungeonRouteChanged($dungeonRoute, $beforeModel, $mapIcon);
                }

                // If we were using a facade before, echo facade locations back so the UI can make sense of that!
                if ($useFacade) {
                    $mapIcon->setAttribute('lat', $originalLatLng->getLat());
                    $mapIcon->setAttribute('lng', $originalLatLng->getLng());
                    $mapIcon->setAttribute('floor_id', $originalLatLng->getFloor()->id);
                    $mapIcon->setRelation('floor', $originalLatLng->getFloor());
                }
            },
            // Can be null, it will then default to the dungeon internally
            $dungeonRoute);
    }

    /**
     * @return array|ResponseFactory|Response
     *
     * @throws Exception
     */
    public function delete(Request $request, ?DungeonRoute $dungeonRoute, MapIcon $mapIcon)
    {
        $dungeonRoute = $mapIcon->dungeonRoute;

        $isAdmin = Auth::check() && Auth::user()->hasRole(Role::ROLE_ADMIN);
        // Must be an admin to use this endpoint like this!
        if (!$isAdmin && ($dungeonRoute === null || $mapIcon->dungeon_route_id === null)) {
            return response(null, StatusCode::FORBIDDEN);
        } // We're editing a map icon for the user, carry on
        else if ($dungeonRoute !== null) {
            // Edit intentional; don't use delete rule because team members shouldn't be able to delete someone else's map comment
            $this->authorize('edit', $dungeonRoute);
        }

        try {
            if ($mapIcon->delete()) {
                if (Auth::check()) {
                    broadcast(new MapIconDeletedEvent($dungeonRoute ?? $mapIcon->floor->dungeon, Auth::user(), $mapIcon));
                }

                // Only when icons that are sticky to the map are saved
                if ($dungeonRoute === null) {
                    // Trigger mapping changed event so the mapping gets saved across all environments
                    $this->mappingChanged($mapIcon, null);
                } else {
                    $this->dungeonRouteChanged($dungeonRoute, $mapIcon, null);

                    $dungeonRoute->touch();
                }

                $result = response()->noContent();
            } else {
                $result = ['result' => 'error'];
            }
        } catch (Exception) {
            $result = response(__('controller.generic.error.not_found'), Http::NOT_FOUND);
        }

        return $result;
    }

    /**
     * @throws AuthorizationException
     * @throws Throwable
     */
    public function dungeonRouteStore(
        CoordinatesServiceInterface $coordinatesService,
        MapIconFormRequest          $request,
        DungeonRoute                $dungeonRoute,
        ?MapIcon                    $mapIcon = null): MapIcon
    {
        return $this->store($coordinatesService, $request, null, $dungeonRoute, $mapIcon);
    }

    /**
     * @throws AuthorizationException
     * @throws Throwable
     */
    public function adminStore(
        CoordinatesServiceInterface $coordinatesService,
        MapIconFormRequest          $request,
        MappingVersion              $mappingVersion,
        ?MapIcon                    $mapIcon = null): MapIcon
    {
        return $this->store($coordinatesService, $request, $mappingVersion, null, $mapIcon);
    }

    /**
     * @return array|ResponseFactory|Response
     *
     * @throws Exception
     */
    public function adminDelete(Request $request, MappingVersion $mappingVersion, MapIcon $mapIcon)
    {
        return $this->delete($request, null, $mapIcon);
    }

    protected function getModelChangedEvent(CoordinatesServiceInterface $coordinatesService, Model $context, User $user, MapIcon|Model $model): ModelChangedEvent
    {
        return new MapIconChangedEvent($coordinatesService, $context, Auth::getUser(), $model);
    }
}
