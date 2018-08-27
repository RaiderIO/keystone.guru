<?php

namespace App\Http\Controllers;

use App\Models\DungeonRoute;
use App\Models\Route;
use App\Models\RouteVertex;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Mockery\Exception;
use Teapot\StatusCode\Http;

class APIRouteController extends Controller
{
    function list(Request $request)
    {
        $floorId = $request->get('floor_id');
        $dungeonRoutePublicKey = $request->get('dungeonroute');
        try {
            $dungeonRoute = $this->_getDungeonRouteFromPublicKey($dungeonRoutePublicKey);
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
                throw new \Exception("Unable to save enemy patrol!");
            } else {
                $route->deleteVertices();

                // Get the new vertices
                $vertices = $request->get('vertices');
                // Store them
                foreach ($vertices as $vertex) {
                    $vertexModel = new RouteVertex();
                    $vertexModel->route_id = $route->id;
                    $vertexModel->lat = $vertex['lat'];
                    $vertexModel->lng = $vertex['lng'];

                    if (!$vertexModel->save()) {
                        throw new \Exception("Unable to save pack vertex!");
                    }
                }
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
            // If we're not the author, don't delete anything
            // @TODO handle this in a policy?
            if ($route->dungeonroute->author_id !== Auth::user()->id) {
                throw new Exception('Unauthorized');
            }

            $route->delete();
            $route->deleteVertices();
            $result = ['result' => 'success'];
        } catch (\Exception $ex) {
            $result = response('Not found', Http::NOT_FOUND);
        }

        return $result;
    }

    function _getDungeonRouteFromPublicKey($publicKey)
    {
        $dungeonRoute = DungeonRoute::where('public_key', '=', $publicKey)->firstOrFail();

        // @TODO handle this in a policy?
        if ($dungeonRoute->author_id !== Auth::user()->id) {
            throw new Exception('Unauthorized');
        }

        return $dungeonRoute;
    }

}
