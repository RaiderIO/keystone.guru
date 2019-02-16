<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\ChecksForDuplicates;
use App\Http\Controllers\Traits\PublicKeyDungeonRoute;
use App\Models\Brushline;
use App\Models\Dungeon;
use App\Models\DungeonRoute;
use App\Models\Polyline;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Mockery\Exception;
use Teapot\StatusCode\Http;

class APIBrushlineController extends Controller
{
    use PublicKeyDungeonRoute;
    use ChecksForDuplicates;

    function list(Request $request)
    {
        $floorId = $request->get('floor_id');
        $dungeonRoutePublicKey = $request->get('dungeonroute');
        try {
            $dungeonRoute = $this->_getDungeonRouteFromPublicKey($dungeonRoutePublicKey, false);
            $result = Brushline::with('polyline')
                ->where('dungeon_route_id', '=', $dungeonRoute->id)
                ->where('floor_id', '=', $floorId)
                ->get();
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
        /** @var Brushline $brushline */
        $brushline = Brushline::findOrNew($request->get('id'));

        /** @var DungeonRoute $dungeonRoute */
        $dungeonRoute = $this->_getDungeonRouteFromPublicKey($request->get('dungeonroute'));

        $brushline->dungeon_route_id = $dungeonRoute->id;
        $brushline->floor_id = $request->get('floor_id');

        // Init to a default value if new
        if (!$brushline->exists) {
            $brushline->polyline_id = -1;
        }

        if (!$brushline->save()) {
            throw new \Exception("Unable to save brushline!");
        } else {
            // Create a new polyline and save it
            /** @var Polyline $polyline */
            $polyline = Polyline::findOrNew($brushline->polyline_id);
            $polyline->model_id = $brushline->id;
            $polyline->model_class = get_class($brushline);
            $polyline->color = $request->get('color');
            $polyline->weight = $request->get('weight');
            $polyline->vertices_json = json_encode($request->get('vertices'));
            $polyline->save();

            // Couple the brushline to the polyline
            $brushline->polyline_id = $polyline->id;
            $brushline->save();

            // @TODO fix this?
            // $this->checkForDuplicateVertices('App\Models\RouteVertex', $vertices);

            // Touch the route so that the thumbnail gets updated
            $dungeonRoute->touch();
        }

        $result = ['id' => $brushline->id];

        return $result;
    }

    function delete(Request $request)
    {
        try {
            /** @var Brushline $brushLine */
            $brushLine = Brushline::findOrFail($request->get('id'));

            // @TODO WTF why does $route->dungeonroute not work?? It will NOT load the relation despite everything being OK?
            /** @var Dungeon $dungeonRoute */
            $dungeonRoute = DungeonRoute::findOrFail($brushLine->dungeon_route_id);
            // If we're not the author, don't delete anything
            // @TODO handle this in a policy?
            if ($dungeonRoute->author_id !== Auth::user()->id && !Auth::user()->hasRole('admin')) {
                throw new Exception('Unauthorized');
            }

            $brushLine->polyline->delete();
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
