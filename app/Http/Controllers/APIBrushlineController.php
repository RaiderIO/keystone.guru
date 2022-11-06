<?php

namespace App\Http\Controllers;

use App\Events\Model\ModelChangedEvent;
use App\Events\Model\ModelDeletedEvent;
use App\Http\Controllers\Traits\ChecksForDuplicates;
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
    use ChecksForDuplicates;
    use SavesPolylines;

    /**
     * @param Request $request
     * @param DungeonRoute $dungeonroute
     * @return Brushline
     * @throws Exception
     */
    function store(Request $request, DungeonRoute $dungeonroute)
    {
        if (!$dungeonroute->isSandbox()) {
            $this->authorize('edit', $dungeonroute);
        }

        /** @var Brushline $brushline */
        $brushline = Brushline::findOrNew($request->get('id'));

        $brushline->dungeon_route_id = $dungeonroute->id;
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
                broadcast(new ModelChangedEvent($dungeonroute, Auth::getUser(), $brushline));
            }

            // Touch the route so that the thumbnail gets updated
            $dungeonroute->touch();
        }

        return $brushline;
    }

    /**
     * @param Request $request
     * @param DungeonRoute $dungeonroute
     * @param Brushline $brushline
     * @return array|ResponseFactory|Response
     * @throws AuthorizationException
     */
    function delete(Request $request, DungeonRoute $dungeonroute, Brushline $brushline)
    {
        if (!$dungeonroute->isSandbox()) {
            // Edit intentional; don't use delete rule because team members shouldn't be able to delete someone else's map comment
            $this->authorize('edit', $dungeonroute);
        }

        try {
            if ($brushline->delete()) {
                if (Auth::check()) {
                    broadcast(new ModelDeletedEvent($dungeonroute, Auth::getUser(), $brushline));
                }

                // Touch the route so that the thumbnail gets updated
                $dungeonroute->touch();

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
