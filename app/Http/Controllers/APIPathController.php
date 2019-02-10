<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\ChecksForDuplicates;
use App\Http\Controllers\Traits\PublicKeyDungeonRoute;
use App\Models\DungeonRoute;
use App\Models\Path;
use App\Models\PathVertex;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Mockery\Exception;
use Teapot\StatusCode\Http;

class APIPathController extends Controller
{
    use PublicKeyDungeonRoute;
    use ChecksForDuplicates;

    function list(Request $request)
    {
        $floorId = $request->get('floor_id');
        $dungeonRoutePublicKey = $request->get('dungeonroute');
        try {
            $dungeonRoute = $this->_getDungeonRouteFromPublicKey($dungeonRoutePublicKey, false);
            $result = Path::where('floor_id', '=', $floorId)->where('dungeon_route_id', '=', $dungeonRoute->id)->get();
        } catch (Exception $ex) {
            $result = response('Not found', Http::NOT_FOUND);
        }

        return $result;
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
            $path->color = $request->get('color');

            if (!$path->save()) {
                throw new \Exception("Unable to save path!");
            } else {
                $path->deleteVertices();

                // Get the new vertices
                $vertices = $request->get('vertices');

                // Store them
                foreach ($vertices as $key => $vertex) {
                    // Assign route to each passed vertex
                    $vertices[$key]['path_id'] = $path->id;
                }

                $this->checkForDuplicateVertices('App\Models\PathVertex', $vertices);

                // Bulk insert
                PathVertex::insert($vertices);

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
            if ($dungeonRoute->author_id !== Auth::user()->id && !Auth::user()->hasRole('admin')) {
                throw new Exception('Unauthorized');
            }

            $path->delete();
            $path->deleteVertices();

            // Touch the route so that the thumbnail gets updated
            $dungeonRoute->touch();

            $result = ['result' => 'success'];
        } catch (\Exception $ex) {
            $result = response('Not found', Http::NOT_FOUND);
        }

        return $result;
    }
}
