<?php

namespace App\Http\Controllers\Ajax;

use App\Events\Models\Brushline\BrushlineChangedEvent;
use App\Events\Models\Brushline\BrushlineDeletedEvent;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\EnforcesDungeonRouteLimits;
use App\Http\Controllers\Traits\SavesPolylines;
use App\Http\Controllers\Traits\ValidatesFloorId;
use App\Http\Requests\Brushline\APIBrushlineFormRequest;
use App\Models\Brushline;
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

class AjaxBrushlineController extends Controller
{
    use EnforcesDungeonRouteLimits;
    use SavesPolylines;
    use ValidatesFloorId;

    /**
     * @return Brushline|Response
     *
     * @throws AuthorizationException
     * @throws Throwable
     */
    public function store(
        APIBrushlineFormRequest     $request,
        CoordinatesServiceInterface $coordinatesService,
        DungeonRoute                $dungeonRoute,
        ?Brushline                  $brushline = null,
    ) {
        $dungeonRoute = $brushline?->dungeonRoute ?? $dungeonRoute; // @phpstan-ignore nullsafe.neverNull

        Gate::authorize('edit', $dungeonRoute);
        $this->abortIfDungeonRouteLimitReached($dungeonRoute, DungeonRouteLimitType::Brushlines);

        $validated = $request->validated();

        $result = $this->validateFloorId($validated['floor_id'], $dungeonRoute->dungeon_id);
        if ($result !== null) {
            return $result;
        }

        try {
            DB::transaction(function () use ($coordinatesService, $brushline, $dungeonRoute, $validated, &$result) {
                $beforeModel = $brushline === null ? null : clone $brushline;

                if ($brushline === null) {
                    $brushline = Brushline::create([
                        'dungeon_route_id' => $dungeonRoute->id,
                        'floor_id'         => $validated['floor_id'],
                        'polyline_id'      => -1,
                    ]);
                    $success = true;
                } else {
                    $success = $brushline->update([
                        'dungeon_route_id' => $dungeonRoute->id,
                        'floor_id'         => $validated['floor_id'],
                    ]);
                }

                if (!$success) {
                    // Caught below, which rolls back this transaction and responds with a 404
                    throw new Exception(__('controller.brushline.error.unable_to_save_brushline'));
                }

                // Create a new polyline and save it
                $this->savePolylineToModel(
                    $coordinatesService,
                    $dungeonRoute,
                    $dungeonRoute->mappingVersion,
                    Polyline::findOrNew($brushline->polyline_id),
                    $beforeModel,
                    $brushline,
                    $validated['polyline'],
                );

                // Touch the route so that the thumbnail gets updated
                $dungeonRoute->touch();

                // Something's updated; broadcast it
                if (Auth::check()) {
                    try {
                        broadcast(new BrushlineChangedEvent($coordinatesService, $dungeonRoute, Auth::user(), $brushline));
                    } catch (BroadcastException) {
                        // We don't really care if the broadcast fails, so just catch the exception and move on
                    }
                }

                $result = $brushline;
            });
        } catch (Exception) {
            $result = response(__('controller.generic.error.not_found'), Http::NOT_FOUND);
        }

        return $result;
    }

    /**
     * @return Response|ResponseFactory
     *
     * @throws AuthorizationException
     */
    public function delete(Request $request, DungeonRoute $dungeonRoute, Brushline $brushline)
    {
        $dungeonRoute = $brushline->dungeonRoute;

        // Edit intentional; don't use delete rule because team members shouldn't be able to delete someone else's brush line
        Gate::authorize('edit', $dungeonRoute);

        try {
            if ($brushline->delete()) {
                if (Auth::check()) {
                    /** @var \App\Models\User $user */
                    $user = Auth::getUser();

                    try {
                        broadcast(new BrushlineDeletedEvent($dungeonRoute, $user, $brushline));
                    } catch (BroadcastException) {
                        // We don't really care if the broadcast fails, so just catch the exception and move on
                    }
                }

                $this->dungeonRouteChanged($dungeonRoute, $brushline, null);

                // Touch the route so that the thumbnail gets updated
                $dungeonRoute->touch();

                $result = response()->noContent();
            } else {
                $result = response(__('controller.brushline.error.unable_to_delete_brushline'), Http::INTERNAL_SERVER_ERROR);
            }
        } catch (Exception) {
            $result = response(__('controller.generic.error.not_found'), Http::NOT_FOUND);
        }

        return $result;
    }
}
