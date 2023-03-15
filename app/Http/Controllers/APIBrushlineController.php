<?php

namespace App\Http\Controllers;

use App\Events\Model\ModelChangedEvent;
use App\Events\Model\ModelDeletedEvent;
use App\Http\Controllers\Traits\SavesPolylines;
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
     * @param Request $request
     * @param DungeonRoute $dungeonRoute
     * @return Brushline
     * @throws Exception
     */
    function store(Request $request, DungeonRoute $dungeonRoute)
    {
        if (!$dungeonRoute->isSandbox()) {
            $this->authorize('edit', $dungeonRoute);
        }

        /** @var Brushline $brushline */
        $brushline = Brushline::findOrNew($request->get('id'));

        $brushline->dungeon_route_id = $dungeonRoute->id;
        $brushline->floor_id         = (int)$request->get('floor_id');

        // Init to a default value if new
        if (!$brushline->exists) {
            $brushline->polyline_id = -1;
        }

        if (!$brushline->save()) {
            throw new Exception("Unable to save brushline!");
        } else {
            // Create a new polyline and save it
            $polyline = $this->savePolyline(Polyline::findOrNew($brushline->polyline_id), $brushline, $request->get('polyline'));

            // Couple the brushline to the polyline
            $brushline->polyline_id = $polyline->id;
            $brushline->save();

            // Refresh the polyline before echoing it out
            $brushline->load(['polyline']);

            if (Auth::check()) {
                broadcast(new ModelChangedEvent($dungeonRoute, Auth::getUser(), $brushline));
            }

            // Touch the route so that the thumbnail gets updated
            $dungeonRoute->touch();
        }

        return $brushline;
    }

    /**
     * @param Request $request
     * @param DungeonRoute $dungeonRoute
     * @param Brushline $brushline
     * @return array|ResponseFactory|Response
     * @throws AuthorizationException
     */
    function delete(Request $request, DungeonRoute $dungeonRoute, Brushline $brushline)
    {
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
