<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\ChecksForDuplicates;
use App\Http\Controllers\Traits\PublicKeyDungeonRoute;
use App\Models\Dungeon;
use App\Models\DungeonRoute;
use App\Models\Polyline;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Mockery\Exception;
use Teapot\StatusCode\Http;

class APIPolylineController extends Controller
{
    use PublicKeyDungeonRoute;
    use ChecksForDuplicates;

    function list(Request $request)
    {
        $floorId = $request->get('floor_id');
        $dungeonRoutePublicKey = $request->get('dungeonroute');
        try {
            $dungeonRoute = $this->_getDungeonRouteFromPublicKey($dungeonRoutePublicKey, false);
            $result = Polyline::where('floor_id', '=', $floorId)->where('dungeon_route_id', '=', $dungeonRoute->id)->get();
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
        /** @var Polyline $polyline */
        $polyline = Polyline::findOrNew($request->get('id'));

        try {
            /** @var DungeonRoute $dungeonRoute */
            $dungeonRoute = $this->_getDungeonRouteFromPublicKey($request->get('dungeonroute'));

            $polyline->dungeon_route_id = $dungeonRoute->id;
            $polyline->floor_id = $request->get('floor_id');
            $polyline->type = $request->get('type');
            $polyline->color = $request->get('color');
            $polyline->weight = $request->get('weight');
            $polyline->vertices_json = json_encode($request->get('vertices'));

            if (!$polyline->save()) {
                throw new \Exception("Unable to save polyline!");
            } else {
                // @TODO fix this?
                // $this->checkForDuplicateVertices('App\Models\RouteVertex', $vertices);

                // Touch the route so that the thumbnail gets updated
                $dungeonRoute->touch();
            }

            $result = ['id' => $polyline->id];
        } catch (Exception $ex) {
            $result = response('Not found', Http::NOT_FOUND);
        }
        return $result;
    }

    function delete(Request $request)
    {
        try {
            /** @var Polyline $brushLine */
            $brushLine = Polyline::findOrFail($request->get('id'));

            // @TODO WTF why does $route->dungeonroute not work?? It will NOT load the relation despite everything being OK?
            /** @var Dungeon $dungeonRoute */
            $dungeonRoute = DungeonRoute::findOrFail($brushLine->dungeon_route_id);
            // If we're not the author, don't delete anything
            // @TODO handle this in a policy?
            if ($dungeonRoute->author_id !== Auth::user()->id && !Auth::user()->hasRole('admin')) {
                throw new Exception('Unauthorized');
            }

            $brushLine->delete();

            // Touch the route so that the thumbnail gets updated
            $dungeonRoute->touch();

            $result = ['result' => 'success'];
        } catch (\Exception $ex) {
            $result = response('Not found', Http::NOT_FOUND);
        }

        return $result;
    }
}
