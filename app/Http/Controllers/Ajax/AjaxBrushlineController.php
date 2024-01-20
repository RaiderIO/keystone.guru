<?php

namespace App\Http\Controllers\Ajax;

use App\Events\Model\ModelChangedEvent;
use App\Events\Model\ModelDeletedEvent;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\SavesPolylines;
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
use Teapot\StatusCode\Http;

class AjaxBrushlineController extends Controller
{
    use SavesPolylines;

    /**
     * @param APIBrushlineFormRequest     $request
     * @param CoordinatesServiceInterface $coordinatesService
     * @param DungeonRoute                $dungeonRoute
     * @param Brushline|null              $brushline
     * @return Brushline
     * @throws AuthorizationException
     */
    function store(
        APIBrushlineFormRequest     $request,
        CoordinatesServiceInterface $coordinatesService,
        DungeonRoute                $dungeonRoute,
        ?Brushline                  $brushline = null
    ) {
        $dungeonRoute = optional($brushline)->dungeonRoute ?? $dungeonRoute;

        $this->authorize('edit', $dungeonRoute);
        $this->authorize('addBrushline', $dungeonRoute);

        $validated = $request->validated();

        if ($brushline === null) {
            $brushline = Brushline::create([
                'dungeon_route_id' => $dungeonRoute->id,
                'floor_id'         => $validated['floor_id'],
                'polyline_id'      => -1,
            ]);
            $success   = $brushline instanceof Brushline;
        } else {
            $success = $brushline->update([
                'dungeon_route_id' => $dungeonRoute->id,
                'floor_id'         => $validated['floor_id'],
            ]);
        }

        try {
            if ($success) {
                // Create a new polyline and save it
                $changedFloor = null;
                $polyline     = $this->savePolyline(
                    $coordinatesService,
                    $dungeonRoute->mappingVersion,
                    Polyline::findOrNew($brushline->polyline_id),
                    $brushline,
                    $validated['polyline'],
                    $changedFloor
                );

                // Couple the path to the polyline
                $brushline->update([
                    'polyline_id' => $polyline->id,
                    'floor_id'    => optional($changedFloor)->id ?? $brushline->floor_id,
                ]);

                // Load the polyline so it can be echoed back to the user
                $brushline->load(['polyline']);

                // Something's updated; broadcast it
                if (Auth::check()) {
                    broadcast(new ModelChangedEvent($dungeonRoute, Auth::user(), $brushline));
                }

                // Touch the route so that the thumbnail gets updated
                $dungeonRoute->touch();
            } else {
                throw new \Exception('Unable to save brushline!');
            }

            $result = $brushline;
        } catch (Exception $ex) {
            $result = response('Not found', Http::NOT_FOUND);
        }

        return $result;
    }

    /**
     * @param Request      $request
     * @param DungeonRoute $dungeonRoute
     * @param Brushline    $brushline
     * @return Response|ResponseFactory
     * @throws AuthorizationException
     */
    function delete(Request $request, DungeonRoute $dungeonRoute, Brushline $brushline)
    {
        $dungeonRoute = $brushline->dungeonRoute;

        // Edit intentional; don't use delete rule because team members shouldn't be able to delete someone else's brush line
        $this->authorize('edit', $dungeonRoute);

        try {
            if ($brushline->delete()) {
                if (Auth::check()) {
                    broadcast(new ModelDeletedEvent($dungeonRoute, Auth::getUser(), $brushline));
                }

                // Touch the route so that the thumbnail gets updated
                $dungeonRoute->touch();

                $result = response()->noContent();
            } else {
                $result = response('Unable to save Brushline', Http::INTERNAL_SERVER_ERROR);
            }
        } catch (Exception $ex) {
            $result = response('Not found', Http::NOT_FOUND);
        }

        return $result;
    }
}
