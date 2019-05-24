<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\ChecksForDuplicates;
use App\Http\Controllers\Traits\ListsBrushlines;
use App\Models\Brushline;
use App\Models\DungeonRoute;
use App\Models\Polyline;
use Illuminate\Http\Request;
use Teapot\StatusCode\Http;

class APIBrushlineController extends Controller
{
    use ChecksForDuplicates;
    use ListsBrushlines;

    function list(Request $request)
    {
        return $this->listBrushlines(
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
        $this->authorize('edit', $dungeonroute);

        /** @var Brushline $brushline */
        $brushline = Brushline::findOrNew($request->get('id'));

        $brushline->dungeon_route_id = $dungeonroute->id;
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
            $dungeonroute->touch();
        }

        $result = ['id' => $brushline->id];

        return $result;
    }

    /**
     * @param Request $request
     * @param DungeonRoute $dungeonroute
     * @param Brushline $brushline
     * @return array|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    function delete(Request $request, DungeonRoute $dungeonroute, Brushline $brushline)
    {
        $this->authorize('edit', $dungeonroute);

        try {
            $brushline->polyline->delete();
            $brushline->delete();

            // Touch the route so that the thumbnail gets updated
            $dungeonroute->touch();

            $result = ['result' => 'success'];
        } catch (\Exception $ex) {
            $result = response('Not found', Http::NOT_FOUND);
        }

        return $result;
    }
}
