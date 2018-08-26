<?php

namespace App\Http\Controllers;

use App\Models\Route;
use App\Models\RouteVertex;
use Illuminate\Http\Request;
use Teapot\StatusCode\Http;

class APIRouteController extends Controller
{
    function list(Request $request)
    {
        $floorId = $request->get('floor_id');
        return Route::all()->where('floor_id', '=', $floorId);
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

        $route->floor_id = $request->get('floor_id');
        $route->enemy_id = $request->get('enemy_id');

        if (!$route->save()) {
            throw new \Exception("Unable to save enemy patrol!");
        } else {
            $route->deleteVertices();

            // Get the new vertices
            $vertices = $request->get('vertices');
            // Store them
            foreach ($vertices as $vertex) {
                $vertexModel = new RouteVertex();
                $vertexModel->enemy_patrol_id = $route->id;
                $vertexModel->lat = $vertex['lat'];
                $vertexModel->lng = $vertex['lng'];

                if (!$vertexModel->save()) {
                    throw new \Exception("Unable to save pack vertex!");
                }
            }
        }

        return ['id' => $route->id];
    }

    function delete(Request $request)
    {
        try {
            /** @var Route $route */
            $route = Route::findOrFail($request->get('id'));

            $route->delete();
            $route->deleteVertices();
            $result = ['result' => 'success'];
        } catch (\Exception $ex) {
            $result = response('Not found', Http::NOT_FOUND);
        }

        return $result;
    }
}
