<?php

namespace App\Http\Controllers\Ajax;

use App\Events\Models\Path\PathChangedEvent;
use App\Events\Models\Path\PathDeletedEvent;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\EnforcesDungeonRouteLimits;
use App\Http\Controllers\Traits\SavesPolylines;
use App\Http\Controllers\Traits\ValidatesFloorId;
use App\Http\Requests\Path\APIPathFormRequest;
use App\Models\Brushline;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonRoute\DungeonRouteLimitType;
use App\Models\Path;
use App\Models\Polyline;
use App\Models\User;
use App\Service\Coordinates\CoordinatesServiceInterface;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Broadcasting\BroadcastException;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Teapot\StatusCode\Http;

class AjaxPathController extends Controller
{
    use EnforcesDungeonRouteLimits;
    use SavesPolylines;
    use ValidatesFloorId;

    /**
     * @return Brushline|Response
     *
     * @throws AuthorizationException
     */
    public function store(
        APIPathFormRequest          $request,
        CoordinatesServiceInterface $coordinatesService,
        DungeonRoute                $dungeonRoute,
        ?Path                       $path = null,
    ) {
        $dungeonRoute = $path?->dungeonRoute ?? $dungeonRoute; // @phpstan-ignore nullsafe.neverNull

        Gate::authorize('edit', $dungeonRoute);
        $this->abortIfDungeonRouteLimitReached($dungeonRoute, DungeonRouteLimitType::Paths);

        $validated = $request->validated();

        $result = $this->validateFloorId($validated['floor_id'], $dungeonRoute->dungeon_id);
        if ($result !== null) {
            return $result;
        }

        try {
            DB::transaction(function () use ($coordinatesService, $path, $dungeonRoute, $validated, &$result) {
                $beforeModel = $path === null ? null : clone $path;

                if ($path === null) {
                    $path = Path::create([
                        'dungeon_route_id' => $dungeonRoute->id,
                        'floor_id'         => $validated['floor_id'],
                        'polyline_id'      => -1,
                    ]);
                    $success = true;
                } else {
                    $success = $path->update([
                        'dungeon_route_id' => $dungeonRoute->id,
                        'floor_id'         => $validated['floor_id'],
                    ]);
                }

                if (! $success) {
                    // Caught below, which rolls back this transaction and responds with a 404
                    throw new \Exception(__('controller.path.error.unable_to_save_path'));
                }

                // Create a new polyline and save it
                $this->savePolylineToModel(
                    $coordinatesService,
                    $dungeonRoute,
                    $dungeonRoute->mappingVersion,
                    Polyline::findOrNew($path->polyline_id),
                    $beforeModel,
                    $path,
                    $validated['polyline'],
                );

                // Set or unset the linked awakened obelisks now that we have an ID
                $path->setLinkedAwakenedObeliskByMapIconId($validated['linked_awakened_obelisk_id'] ?? null);

                // Something's updated; broadcast it
                if (Auth::check()) {
                    /** @var User $user */
                    $user = Auth::getUser();

                    try {
                        broadcast(new PathChangedEvent($dungeonRoute, $user, $path));
                    } catch (BroadcastException) {
                        // Ignore broadcast failures
                    }
                }

                // Touch the route so that the thumbnail gets updated
                $dungeonRoute->touch();

                $result = $path;
            });
        } catch (\Exception) {
            $result = response(__('controller.generic.error.not_found'), Http::NOT_FOUND);
        }

        return $result;
    }

    /**
     * Returns the coordinate data that was dropped from the path-changed broadcast (#3909) -
     * collaborating clients call this after receiving that event instead.
     *
     * @return array<string, mixed>
     *
     * @throws AuthorizationException
     */
    public function show(CoordinatesServiceInterface $coordinatesService, DungeonRoute $dungeonRoute, Path $path): array
    {
        $dungeonRoute = $path->dungeonRoute;

        Gate::authorize('view', $dungeonRoute);

        return [
            'model_data' => $path->polyline->getCoordinatesData(
                $coordinatesService,
                $dungeonRoute->mappingVersion,
                $path->floor,
            ),
        ];
    }

    /**
     * @return array<string, mixed>|ResponseFactory|Response
     *
     * @throws AuthorizationException
     */
    public function delete(Request $request, DungeonRoute $dungeonRoute, Path $path): array|ResponseFactory|Response
    {
        $dungeonRoute = $path->dungeonRoute;

        // Edit intentional; don't use delete rule because team members shouldn't be able to delete someone else's path
        Gate::authorize('edit', $dungeonRoute);

        try {
            $deleted = DB::transaction(function () use ($dungeonRoute, $path): bool {
                // Nothing has been written yet, so there is nothing to roll back
                if (!$path->delete()) {
                    return false;
                }

                $this->dungeonRouteChanged($dungeonRoute, $path, null);

                // Touch the route so that the thumbnail gets updated
                $dungeonRoute->touch();

                return true;
            });

            if ($deleted) {
                // Broadcast only once the delete is committed, so no listener can read pre-commit state
                if (Auth::check()) {
                    /** @var User $user */
                    $user = Auth::getUser();

                    try {
                        broadcast(new PathDeletedEvent($dungeonRoute, $user, $path));
                    } catch (BroadcastException) {
                        // Ignore broadcast failures
                    }
                }

                $result = response()->noContent();
            } else {
                $result = response(__('controller.path.error.unable_to_delete_path'), Http::INTERNAL_SERVER_ERROR);
            }
        } catch (\Exception) {
            $result = response(__('controller.generic.error.not_found'), Http::NOT_FOUND);
        }

        return $result;
    }
}
