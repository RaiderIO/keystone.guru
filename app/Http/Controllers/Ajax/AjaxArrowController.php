<?php

namespace App\Http\Controllers\Ajax;

use App\Events\Models\Arrow\ArrowChangedEvent;
use App\Events\Models\Arrow\ArrowDeletedEvent;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\EnforcesDungeonRouteLimits;
use App\Http\Controllers\Traits\SavesPolylines;
use App\Http\Controllers\Traits\ValidatesFloorId;
use App\Http\Requests\Arrow\APIArrowFormRequest;
use App\Models\Arrow;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonRoute\DungeonRouteLimitType;
use App\Models\Polyline;
use App\Service\Coordinates\CoordinatesServiceInterface;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Broadcasting\BroadcastException;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Teapot\StatusCode\Http;
use Throwable;

class AjaxArrowController extends Controller
{
    use EnforcesDungeonRouteLimits;
    use SavesPolylines;
    use ValidatesFloorId;

    /**
     * @return Arrow|Response
     *
     * @throws AuthorizationException
     * @throws Throwable
     */
    public function store(
        APIArrowFormRequest         $request,
        CoordinatesServiceInterface $coordinatesService,
        DungeonRoute                $dungeonRoute,
        ?Arrow                      $arrow = null,
    ) {
        $dungeonRoute = $arrow?->dungeonRoute ?? $dungeonRoute; // @phpstan-ignore nullsafe.neverNull

        Gate::authorize('edit', $dungeonRoute);
        $this->abortIfDungeonRouteLimitReached($dungeonRoute, DungeonRouteLimitType::Arrows);

        $validated = $request->validated();

        $result = $this->validateFloorId($validated['floor_id'], $dungeonRoute->dungeon_id);
        if ($result !== null) {
            return $result;
        }

        try {
            DB::transaction(function () use ($coordinatesService, $arrow, $dungeonRoute, $validated, &$result) {
                $beforeModel = $arrow === null ? null : clone $arrow;

                if ($arrow === null) {
                    $arrow = Arrow::create([
                        'dungeon_route_id' => $dungeonRoute->id,
                        'floor_id'         => $validated['floor_id'],
                        'polyline_id'      => -1,
                    ]);
                    $success = true;
                } else {
                    $success = $arrow->update([
                        'dungeon_route_id' => $dungeonRoute->id,
                        'floor_id'         => $validated['floor_id'],
                    ]);
                }

                if (!$success) {
                    // Caught below, which rolls back this transaction and responds with a 404
                    throw new Exception(__('controller.arrow.error.unable_to_save_arrow'));
                }

                $this->savePolylineToModel(
                    $coordinatesService,
                    $dungeonRoute,
                    $dungeonRoute->mappingVersion,
                    Polyline::findOrNew($arrow->polyline_id),
                    $beforeModel,
                    $arrow,
                    $validated['polyline'],
                );

                $dungeonRoute->touch();

                if (Auth::check()) {
                    try {
                        broadcast(new ArrowChangedEvent($dungeonRoute, Auth::user(), $arrow));
                    } catch (BroadcastException) {
                        // Ignore broadcast failures
                    }
                }

                $result = $arrow;
            });
        } catch (Exception) {
            $result = response(__('controller.generic.error.not_found'), Http::NOT_FOUND);
        }

        return $result;
    }

    /**
     * Returns the coordinate data that was dropped from the arrow-changed broadcast (#3909) -
     * collaborating clients call this after receiving that event instead.
     *
     * @return array<string, mixed>
     *
     * @throws AuthorizationException
     */
    public function show(CoordinatesServiceInterface $coordinatesService, DungeonRoute $dungeonRoute, Arrow $arrow): array
    {
        $dungeonRoute = $arrow->dungeonRoute;

        Gate::authorize('view', $dungeonRoute);

        return [
            'model_data' => $arrow->polyline->getCoordinatesData(
                $coordinatesService,
                $dungeonRoute->mappingVersion,
                $arrow->floor,
            ),
        ];
    }

    /**
     * @return Response|ResponseFactory
     *
     * @throws AuthorizationException
     */
    public function delete(Request $request, DungeonRoute $dungeonRoute, Arrow $arrow)
    {
        // route:cache serializes this method; a body whose only $this usage sits inside a
        // nested closure is reconstructed unbound. Delegating keeps a top-level $this read
        // here, and the closures below compile normally inside a regular method (#4329).
        return $this->deleteArrow($request, $dungeonRoute, $arrow);
    }

    /**
     * @return Response|ResponseFactory
     *
     * @throws AuthorizationException
     */
    private function deleteArrow(Request $request, DungeonRoute $dungeonRoute, Arrow $arrow)
    {
        $dungeonRoute = $arrow->dungeonRoute;

        Gate::authorize('edit', $dungeonRoute);

        try {
            $deleted = DB::transaction(function () use ($dungeonRoute, $arrow): bool {
                // Nothing has been written yet, so there is nothing to roll back
                if (!$arrow->delete()) {
                    return false;
                }

                $this->dungeonRouteChanged($dungeonRoute, $arrow, null);

                $dungeonRoute->touch();

                return true;
            });

            if ($deleted) {
                // Broadcast only once the delete is committed, so no listener can read pre-commit state
                if (Auth::check()) {
                    /** @var \App\Models\User $user */
                    $user = Auth::getUser();

                    try {
                        broadcast(new ArrowDeletedEvent($dungeonRoute, $user, $arrow));
                    } catch (BroadcastException) {
                        // Ignore broadcast failures
                    }
                }

                $result = response()->noContent();
            } else {
                $result = response(__('controller.arrow.error.unable_to_delete_arrow'), Http::INTERNAL_SERVER_ERROR);
            }
        } catch (Exception) {
            $result = response(__('controller.generic.error.not_found'), Http::NOT_FOUND);
        }

        return $result;
    }
}
