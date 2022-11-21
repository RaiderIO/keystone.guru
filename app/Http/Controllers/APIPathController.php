<?php

namespace App\Http\Controllers;

use App\Events\Model\ModelChangedEvent;
use App\Events\Model\ModelDeletedEvent;
use App\Http\Controllers\Traits\SavesPolylines;
use App\Models\DungeonRoute;
use App\Models\Path;
use App\Models\Polyline;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Mockery\Exception;
use Teapot\StatusCode\Http;

class APIPathController extends Controller
{
    use SavesPolylines;

    /**
     * @param Request $request
     * @param DungeonRoute $dungeonroute
     * @return Path
     * @throws \Exception
     */
    function store(Request $request, DungeonRoute $dungeonroute)
    {
        if (!$dungeonroute->isSandbox()) {
            $this->authorize('edit', $dungeonroute);
        }

        /** @var Path $path */
        $path = Path::findOrNew($request->get('id'));

        try {
            $path->dungeon_route_id = $dungeonroute->id;
            $path->floor_id         = (int)$request->get('floor_id');

            // Init to a default value if new
            if (!$path->exists) {
                $path->polyline_id = -1;
            }

            if ($path->save()) {
                // Create a new polyline and save it
                $polyline = $this->savePolyline(Polyline::findOrNew($path->polyline_id), $path, $request->get('polyline'));

                // Couple the path to the polyline
                $path->polyline_id = $polyline->id;
                $path->save();

                // Load the polyline so it can be echoed back to the user
                $path->load(['polyline']);

                // Set or unset the linked awakened obelisks now that we have an ID
                $path->setLinkedAwakenedObeliskByMapIconId($request->get('linked_awakened_obelisk_id', null));

                // Something's updated; broadcast it
                if (Auth::check()) {
                    broadcast(new ModelChangedEvent($dungeonroute, Auth::user(), $path));
                }

                // Touch the route so that the thumbnail gets updated
                $dungeonroute->touch();
            } else {
                throw new \Exception('Unable to save path!');
            }

            $result = $path;
        } catch (Exception $ex) {
            $result = response('Not found', Http::NOT_FOUND);
        }
        return $result;
    }

    /**
     * @param Request $request
     * @param DungeonRoute $dungeonroute
     * @param Path $path
     * @return array|ResponseFactory|Response
     * @throws AuthorizationException
     */
    function delete(Request $request, DungeonRoute $dungeonroute, Path $path)
    {
        // Edit intentional; don't use delete rule because team members shouldn't be able to delete someone else's route
        if (!$dungeonroute->isSandbox()) {
            $this->authorize('edit', $dungeonroute);
        }

        try {
            if ($path->delete()) {
                if (Auth::check()) {
                    broadcast(new ModelDeletedEvent($dungeonroute, Auth::user(), $path));
                }

                // Touch the route so that the thumbnail gets updated
                $dungeonroute->touch();

                $result = response()->noContent();
            } else {
                $result = response('Unable to save Path', Http::INTERNAL_SERVER_ERROR);
            }
        } catch (\Exception $ex) {
            $result = response('Not found', Http::NOT_FOUND);
        }

        return $result;
    }
}
