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
                        broadcast(new ArrowChangedEvent($coordinatesService, $dungeonRoute, Auth::user(), $arrow));
                    } catch (BroadcastException) {
                        // Ignore broadcast failures
                    }
                }

                $result = $arrow;
            });
        } catch (Exception) {
            // Caught out here rather than inside the closure: a catch within the closure lets it
            // return normally, so the transaction commits the half-written arrow (a row still
            // carrying the polyline_id = -1 sentinel, plus an orphan polyline) while the response
            // tells the client the request failed (#4259).
            $result = response(__('controller.generic.error.not_found'), Http::NOT_FOUND);
        }

        return $result;
    }

    /**
     * @return Response|ResponseFactory
     *
     * @throws AuthorizationException
     */
    public function delete(Request $request, DungeonRoute $dungeonRoute, Arrow $arrow)
    {
        $dungeonRoute = $arrow->dungeonRoute;

        Gate::authorize('edit', $dungeonRoute);

        try {
            if ($arrow->delete()) {
                if (Auth::check()) {
                    /** @var \App\Models\User $user */
                    $user = Auth::getUser();

                    try {
                        broadcast(new ArrowDeletedEvent($dungeonRoute, $user, $arrow));
                    } catch (BroadcastException) {
                        // Ignore broadcast failures
                    }
                }

                $this->dungeonRouteChanged($dungeonRoute, $arrow, null);

                $dungeonRoute->touch();

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
