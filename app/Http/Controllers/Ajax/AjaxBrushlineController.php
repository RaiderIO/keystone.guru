<?php

namespace App\Http\Controllers\Ajax;

use App\Events\Models\Brushline\BrushlineChangedEvent;
use App\Events\Models\Brushline\BrushlineDeletedEvent;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\SavesPolylines;
use App\Http\Controllers\Traits\ValidatesFloorId;
use App\Http\Requests\Brushline\APIBrushlineFormRequest;
use App\Models\Brushline;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Polyline;
use App\Service\Coordinates\CoordinatesServiceInterface;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Teapot\StatusCode\Http;
use Throwable;

class AjaxBrushlineController extends Controller
{
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
        $test         = "";
        $dungeonRoute = $brushline?->dungeonRoute ?? $dungeonRoute;

        $this->authorize('edit', $dungeonRoute);
        $this->authorize('addBrushline', $dungeonRoute);

        $validated = $request->validated();

        $result = $this->validateFloorId($validated['floor_id'], $dungeonRoute->dungeon_id);
        if ($result !== null) {
            return $result;
        }

        DB::transaction(function () use ($coordinatesService, $brushline, $dungeonRoute, $validated, &$result) {
            $beforeModel = $brushline === null ? null : clone $brushline;

            if ($brushline === null) {
                $brushline = Brushline::create([
                    'dungeon_route_id' => $dungeonRoute->id,
                    'floor_id'         => $validated['floor_id'],
                    'polyline_id'      => -1,
                ]);
                $success = $brushline instanceof Brushline;
            } else {
                $success = $brushline->update([
                    'dungeon_route_id' => $dungeonRoute->id,
                    'floor_id'         => $validated['floor_id'],
                ]);
            }

            try {
                if ($success) {
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

                    // Something's updated; broadcast it
                    if (Auth::check()) {
                        broadcast(new BrushlineChangedEvent($coordinatesService, $dungeonRoute, Auth::user(), $brushline));
                    }

                    // Touch the route so that the thumbnail gets updated
                    $dungeonRoute->touch();
                } else {
                    throw new Exception(__('controller.brushline.error.unable_to_save_brushline'));
                }

                $result = $brushline;
            } catch (Exception) {
                $result = response(__('controller.generic.error.not_found'), Http::NOT_FOUND);
            }
        });

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
        $this->authorize('edit', $dungeonRoute);

        try {
            if ($brushline->delete()) {
                if (Auth::check()) {
                    broadcast(new BrushlineDeletedEvent($dungeonRoute, Auth::getUser(), $brushline));
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
