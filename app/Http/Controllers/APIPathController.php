<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\ChecksForDuplicates;
use App\Http\Controllers\Traits\ListsPaths;
use App\Http\Controllers\Traits\PublicKeyDungeonRoute;
use App\Models\DungeonRoute;
use App\Models\Path;
use App\Models\Polyline;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Mockery\Exception;
use Teapot\StatusCode\Http;

class APIPathController extends Controller
{
    use PublicKeyDungeonRoute;
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
     * @return array
     * @throws \Exception
     */
    function store(Request $request)
    {
        /** @var Path $path */
        $path = Path::findOrNew($request->get('id'));

        try {
            $dungeonRoute = $this->_getDungeonRouteFromPublicKey($request->get('dungeonroute'));

            $path->dungeon_route_id = $dungeonRoute->id;
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

                // Touch the route so that the thumbnail gets updated
                $dungeonRoute->touch();
            }

            $result = ['id' => $path->id];
        } catch (Exception $ex) {
            $result = response('Not found', Http::NOT_FOUND);
        }
        return $result;
    }

    function delete(Request $request)
    {
        try {
            /** @var Path $path */
            $path = Path::findOrFail($request->get('id'));

            // @TODO WTF why does $route->dungeonroute not work?? It will NOT load the relation despite everything being OK?
            /** @var DungeonRoute $dungeonRoute */
            $dungeonRoute = DungeonRoute::findOrFail($path->dungeon_route_id);
            // If we're not the author, don't delete anything
            // @TODO handle this in a policy?
            if (!Auth::check() || ($dungeonRoute->author_id !== Auth::user()->id && !Auth::user()->hasRole('admin'))) {
                throw new Exception('Unauthorized');
            }

            $path->polyline->delete();
            $path->delete();

            // Touch the route so that the thumbnail gets updated
            $dungeonRoute->touch();

            $result = ['result' => 'success'];
        } catch (\Exception $ex) {
            $result = response('Not found', Http::NOT_FOUND);
        }

        return $result;
    }
}
