<?php

namespace App\Http\Controllers;

use App\Events\Model\ModelChangedEvent;
use App\Events\Model\ModelDeletedEvent;
use App\Http\Controllers\Traits\SavesPolylines;
use App\Http\Requests\Brushline\APIBrushlineFormRequest;
use App\Models\Brushline;
use App\Models\DungeonRoute;
use App\Models\Polyline;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Teapot\StatusCode\Http;

class APIBrushlineController extends Controller
{
    use SavesPolylines;

    /**
     * @param APIBrushlineFormRequest $request
     * @param DungeonRoute $dungeonRoute
     * @param Brushline|null $brushline
     * @return Brushline
     * @throws AuthorizationException
     */
    function store(APIBrushlineFormRequest $request, DungeonRoute $dungeonRoute, ?Brushline $brushline = null)
    {
        $dungeonRoute = optional($brushline)->dungeonRoute ?? $dungeonRoute;

        if (!$dungeonRoute->isSandbox()) {
            $this->authorize('edit', $dungeonRoute);
        }

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
                $polyline = $this->savePolyline(Polyline::findOrNew($brushline->polyline_id), $brushline, $validated['polyline']);

                // Couple the path to the polyline
                $brushline->update([
                    'polyline_id' => $polyline->id,
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
     * @param Request $request
     * @param DungeonRoute $dungeonRoute
     * @param Brushline $brushline
     * @return Response|ResponseFactory
     * @throws AuthorizationException
     */
    function delete(Request $request, DungeonRoute $dungeonRoute, Brushline $brushline)
    {
        $dungeonRoute = $brushline->dungeonRoute;

        if (!$dungeonRoute->isSandbox()) {
            // Edit intentional; don't use delete rule because team members shouldn't be able to delete someone else's map comment
            $this->authorize('edit', $dungeonRoute);
        }

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
