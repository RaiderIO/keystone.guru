<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\ChecksForDuplicates;
use App\Http\Controllers\Traits\PublicKeyDungeonRoute;
use App\Models\DungeonRoute;
use App\Models\Route;
use App\Models\RouteVertex;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Mockery\Exception;
use Teapot\StatusCode\Http;

class APIRouteController extends Controller
{
    use PublicKeyDungeonRoute;
    use ChecksForDuplicates;

    function list(Request $request)
    {
        $floorId = $request->get('floor_id');
        $dungeonRoutePublicKey = $request->get('dungeonroute');
        try {
            $dungeonRoute = $this->_getDungeonRouteFromPublicKey($dungeonRoutePublicKey, false);
            $result = Route::where('floor_id', '=', $floorId)->where('dungeon_route_id', '=', $dungeonRoute->id)->get();
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
        /** @var Route $route */
        $route = Route::findOrNew($request->get('id'));

        try {
            $dungeonRoute = $this->_getDungeonRouteFromPublicKey($request->get('dungeonroute'));

            $route->dungeon_route_id = $dungeonRoute->id;
            $route->floor_id = $request->get('floor_id');
            $route->color = $request->get('color');

            if (!$route->save()) {
                throw new \Exception("Unable to save route!");
            } else {
                $route->deleteVertices();

                // Get the new vertices
                $vertices = $request->get('vertices');

                // Store them
                foreach ($vertices as $key => $vertex) {
                    // Assign route to each passed vertex
                    $vertices[$key]['route_id'] = $route->id;
                }

                $this->checkForDuplicateVertices('App\Models\RouteVertex', $vertices);

                // Bulk insert
                RouteVertex::insert($vertices);

                // Touch the route so that the thumbnail gets updated
                $dungeonRoute->touch();
            }

            $result = ['id' => $route->id];
        } catch (Exception $ex) {
            $result = response('Not found', Http::NOT_FOUND);
        }
        return $result;
    }

    function delete(Request $request)
    {
        try {
            /** @var Route $route */
            $route = Route::findOrFail($request->get('id'));

            // @TODO WTF why does $route->dungeonroute not work?? It will NOT load the relation despite everything being OK?
            $dungeonRoute = DungeonRoute::findOrFail($route->dungeon_route_id);
            // If we're not the author, don't delete anything
            // @TODO handle this in a policy?
            if ($dungeonRoute->author_id !== Auth::user()->id && !Auth::user()->hasRole('admin')) {
                throw new Exception('Unauthorized');
            }

            $route->delete();
            $route->deleteVertices();

            // Touch the route so that the thumbnail gets updated
            $dungeonRoute->touch();

            $result = ['result' => 'success'];
        } catch (\Exception $ex) {
            $result = response('Not found', Http::NOT_FOUND);
        }

        return $result;
    }
}
