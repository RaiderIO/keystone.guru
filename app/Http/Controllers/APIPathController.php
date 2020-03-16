<?php

namespace App\Http\Controllers;

use App\Events\PathChangedEvent;
use App\Events\PathDeletedEvent;
use App\Http\Controllers\Traits\ChecksForDuplicates;
use App\Http\Controllers\Traits\ListsPaths;
use App\Models\DungeonRoute;
use App\Models\Path;
use App\Models\Polyline;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Mockery\Exception;
use Teapot\StatusCode\Http;

class APIPathController extends Controller
{
    use ChecksForDuplicates;
    use ListsPaths;

    function list(Request $request)
    {
        return $this->listPaths(
            $request->get('floor_id'),
            $request->get('dungeonroute')
        );
    }

    /**
     * @param Request $request
     * @param DungeonRoute $dungeonroute
     * @return array
     * @throws \Exception
     */
    function store(Request $request, DungeonRoute $dungeonroute)
    {
        if (!$dungeonroute->isTry()) {
            $this->authorize('edit', $dungeonroute);
        }

        /** @var Path $path */
        $path = Path::findOrNew($request->get('id'));

        try {
            $path->dungeon_route_id = $dungeonroute->id;
            $path->floor_id = $request->get('floor_id');

            // Init to a default value if new
            if (!$path->exists) {
                $path->polyline_id = -1;
            }

            if (!$path->save()) {
                throw new \Exception("Unable to save path!");
            } else {
                // Create a new polyline and save it
                /** @var Polyline $polyline */
                $polyline = Polyline::findOrNew($path->polyline_id);
                $polyline->model_id = $path->id;
                $polyline->model_class = get_class($path);
                $polyline->color = $request->get('color');
                $polyline->weight = $request->get('weight');
                $polyline->vertices_json = json_encode($request->get('vertices'));
                $polyline->save();

                // Couple the path to the polyline
                $path->polyline_id = $polyline->id;
                $path->save();

                // Something's updated; broadcast it
                if (Auth::check()) {
                    broadcast(new PathChangedEvent($dungeonroute, $path, Auth::user()));
                }

                // Touch the route so that the thumbnail gets updated
                $dungeonroute->touch();
            }

            $result = ['id' => $path->id];
        } catch (Exception $ex) {
            $result = response('Not found', Http::NOT_FOUND);
        }
        return $result;
    }

    /**
     * @param Request $request
     * @param DungeonRoute $dungeonroute
     * @param Path $path
     * @return array|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    function delete(Request $request, DungeonRoute $dungeonroute, Path $path)
    {
        // Edit intentional; don't use delete rule because team members shouldn't be able to delete someone else's route
        if (!$dungeonroute->isTry()) {
            $this->authorize('edit', $dungeonroute);
        }

        try {

            if ($path->delete()) {
                if (Auth::check()) {
                    broadcast(new PathDeletedEvent($dungeonroute, $path, Auth::user()));
                }

                // Touch the route so that the thumbnail gets updated
                $dungeonroute->touch();

                $result = ['result' => 'success'];
            } else {
                $result = ['result' => 'error'];
            }

        } catch (\Exception $ex) {
            $result = response('Not found', Http::NOT_FOUND);
        }

        return $result;
    }
}
