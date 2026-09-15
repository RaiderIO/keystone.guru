<?php

namespace App\Http\Controllers\Ajax;

use App\Events\Models\MapIcon\MapIconChangedEvent;
use App\Events\Models\MapIcon\MapIconDeletedEvent;
use App\Events\Models\ModelChangedEvent;
use App\Http\Controllers\Traits\EnforcesDungeonRouteLimits;
use App\Http\Requests\MapIcon\MapIconFormRequest;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonRoute\DungeonRouteLimitType;
use App\Models\MapIcon;
use App\Models\Mapping\MappingModelInterface;
use App\Models\Mapping\MappingVersion;
use App\Models\Team;
use App\Models\User;
use App\Service\Coordinates\CoordinatesServiceInterface;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Broadcasting\BroadcastException;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Override;
use Teapot\StatusCode\Http;
use Throwable;

class AjaxMapIconController extends AjaxMappingModelBaseController
{
    use EnforcesDungeonRouteLimits;

    #[Override]
    protected function shouldCallMappingChanged(
        ?MappingModelInterface $beforeModel,
        ?MappingModelInterface $afterModel,
    ): bool {
        /** @var MapIcon|null $beforeModel */
        /** @var MapIcon|null $afterModel */
        return $beforeModel?->dungeon_route_id === null || $afterModel?->dungeon_route_id === null;
    }

    /**
     * @param  CoordinatesServiceInterface $coordinatesService
     * @param  MapIconFormRequest          $request
     * @param  MappingVersion|null         $mappingVersion     Set -> admin endpoint,
     * @param  DungeonRoute|null           $dungeonRoute       Set -> route edit endpoint
     * @param  MapIcon|null                $mapIcon
     * @return MapIcon
     *
     * @throws AuthorizationException
     * @throws Throwable
     */
    public function store(
        CoordinatesServiceInterface $coordinatesService,
        MapIconFormRequest          $request,
        ?MappingVersion             $mappingVersion,
        ?DungeonRoute               $dungeonRoute,
        ?MapIcon                    $mapIcon = null,
    ): MapIcon {
        $dungeonRoute                  = $mapIcon?->dungeonRoute ?? $dungeonRoute; // @phpstan-ignore nullsafe.neverNull
        $validated                     = $request->validated();
        $validated['dungeon_route_id'] = $dungeonRoute?->id;

        // The team is only assigned further down, once the assignToTeam gate has passed for it
        $requestedTeamId      = $validated['team_id'];
        $validated['team_id'] = null;

        // No dungeon route means this icon is part of the mapping itself - admin only
        if ($dungeonRoute === null) {
            Gate::authorize('createGlobal', MapIcon::class);
        } // We're editing a map comment for the user, carry on
        else {
            Gate::authorize('edit', $dungeonRoute);
            $this->abortIfDungeonRouteLimitReached($dungeonRoute, DungeonRouteLimitType::MapIcons);
        }

        /** @var MapIcon */
        return $this->storeModel(
            $coordinatesService,
            $mappingVersion,
            $validated,
            MapIcon::class,
            $mapIcon,
            static function (MapIcon $mapIcon) use ($validated, $requestedTeamId, $dungeonRoute) {
                // Prevent people being able to update icons that only the admin should if they're supplying a valid dungeon route
                if ($mapIcon->exists && $dungeonRoute !== null) {
                    Gate::authorize('update', $mapIcon);
                }

                // Set the team_id if the user has the rights to do this. May be null if not set or no rights for it.
                $teamId = $requestedTeamId;
                if ($teamId !== null && Gate::allows('assignToTeam', [$mapIcon, Team::find($teamId)])) {
                    $mapIcon->update([
                        'team_id'          => $teamId,
                        'dungeon_route_id' => null,
                    ]);
                }

                // Set or unset the linked awakened obelisks now that we have an ID
                $mapIcon->setLinkedAwakenedObeliskByMapIconId($validated['linked_awakened_obelisk_id']);
            },
            // Can be null, it will then default to the dungeon internally
            $dungeonRoute,
        );
    }

    /**
     * @return array<string, mixed>|ResponseFactory|Response
     *
     * @throws Exception
     */
    public function delete(Request $request, ?DungeonRoute $dungeonRoute, MapIcon $mapIcon): array|ResponseFactory|Response
    {
        // route:cache serializes this method; a body whose only $this usage sits inside a
        // nested closure is reconstructed unbound. Delegating keeps a top-level $this read
        // here, and the closures below compile normally inside a regular method (#4329).
        return $this->deleteMapIcon($request, $dungeonRoute, $mapIcon);
    }

    /**
     * @return array<string, mixed>|ResponseFactory|Response
     *
     * @throws Exception
     */
    private function deleteMapIcon(Request $request, ?DungeonRoute $dungeonRoute, MapIcon $mapIcon): array|ResponseFactory|Response
    {
        $dungeonRoute = $mapIcon->dungeonRoute;

        // Anything not attached to a dungeon route is part of the mapping - admin only
        Gate::authorize('delete', $mapIcon);

        if ($dungeonRoute !== null) {
            // Edit intentional; don't use delete rule because team members shouldn't be able to delete someone else's map comment
            Gate::authorize('edit', $dungeonRoute);
        }

        try {
            $deleted = DB::transaction(function () use ($dungeonRoute, $mapIcon): bool {
                // Nothing has been written yet, so there is nothing to roll back
                if (!$mapIcon->delete()) {
                    return false;
                }

                // Only when icons that are sticky to the map are saved
                if ($dungeonRoute === null) {
                    // Trigger mapping changed event so the mapping gets saved across all environments
                    $this->mappingChanged($mapIcon, null);
                } else {
                    $this->dungeonRouteChanged($dungeonRoute, $mapIcon, null);

                    $dungeonRoute->touch();
                }

                return true;
            });

            if ($deleted) {
                // Broadcast only once the delete is committed, so no listener can read pre-commit state
                if (Auth::check()) {
                    try {
                        broadcast(new MapIconDeletedEvent($dungeonRoute ?? $mapIcon->floor->dungeon, Auth::user(), $mapIcon));
                    } catch (BroadcastException) {
                        // Ignore broadcast failures
                    }
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
        ?MapIcon                    $mapIcon = null,
    ): MapIcon {
        if ($mapIcon !== null) {
            Gate::authorize('update', $mapIcon);

            // A team icon is bound to its team instead of to a route, so it has no route to match against
            $isTeamIcon = $mapIcon->dungeon_route_id === null &&
                Gate::allows('assignToTeam', [$mapIcon, $mapIcon->team]);

            abort_if(
                $mapIcon->dungeon_route_id !== $dungeonRoute->id && !$isTeamIcon,
                Http::FORBIDDEN,
            );
        }

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
        ?MapIcon                    $mapIcon = null,
    ): MapIcon {
        return $this->store($coordinatesService, $request, $mappingVersion, null, $mapIcon);
    }

    /**
     * @return array<string, mixed>|ResponseFactory|Response
     *
     * @throws Exception
     */
    public function adminDelete(Request $request, MappingVersion $mappingVersion, MapIcon $mapIcon): array|ResponseFactory|Response
    {
        return $this->delete($request, null, $mapIcon);
    }

    protected function getModelChangedEvent(
        CoordinatesServiceInterface $coordinatesService,
        Model                       $context,
        User                        $user,
        MapIcon|Model               $model,
    ): ModelChangedEvent {
        /** @var \App\Models\User $authUser */
        $authUser = Auth::getUser();

        return new MapIconChangedEvent($coordinatesService, $context, $authUser, $model);
    }
}
