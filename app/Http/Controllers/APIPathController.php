<?php

namespace App\Http\Controllers;

use App\Events\ModelChangedEvent;
use App\Events\ModelDeletedEvent;
use App\Http\Controllers\Traits\ChecksForDuplicates;
use App\Http\Controllers\Traits\ListsPaths;
use App\Models\DungeonRoute;
use App\Models\PaidTier;
use App\Models\Path;
use App\Models\Polyline;
use App\User;
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

            if ($path->save()) {
                // Create a new polyline and save it
                /** @var Polyline $polyline */
                $polyline = Polyline::findOrNew($path->polyline_id);
                $polyline->model_id = $path->id;
                $polyline->model_class = get_class($path);
                $polyline->color = $request->get('color', '#f00');
                // Only set the animated color if the user has paid for it
                if (Auth::check() && User::findOrFail(Auth::id())->hasPaidTier(PaidTier::ANIMATED_POLYLINES)) {
                    $colorAnimated = $request->get('color_animated', null);
                    $polyline->color_animated = empty($colorAnimated) ? null : $colorAnimated;
                }
                $polyline->weight = $request->get('weight', 2);
                $polyline->vertices_json = json_encode($request->get('vertices'));
                $polyline->save();

                // Couple the path to the polyline
                $path->polyline_id = $polyline->id;
                $path->save();

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
