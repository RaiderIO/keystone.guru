<?php

namespace App\Http\Controllers;

use App\Events\ModelChangedEvent;
use App\Events\ModelDeletedEvent;
use App\Http\Controllers\Traits\ChecksForDuplicates;
use App\Http\Controllers\Traits\ListsEnemyPatrols;
use App\Models\EnemyPatrol;
use App\Models\EnemyPatrolVertex;
use App\Models\Polyline;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Teapot\StatusCode\Http;

class APIEnemyPatrolController extends Controller
{
    use ChecksForDuplicates;
    use ListsEnemyPatrols;

    function list(Request $request)
    {
        return $this->listEnemyPatrols($request->get('floor_id'));
    }

    /**
     * @param Request $request
     * @return array
     * @throws \Exception
     */
    function store(Request $request)
    {
        /** @var EnemyPatrol $enemyPatrol */
        $enemyPatrol = EnemyPatrol::findOrNew($request->get('id'));

        $enemyPatrol->floor_id = $request->get('floor_id');
        $enemyPatrol->teeming = $request->get('teeming');
        $enemyPatrol->faction = $request->get('faction', 'any');

        // Init to a default value if new
        if (!$enemyPatrol->exists) {
            $enemyPatrol->polyline_id = -1;
        }

        if ($enemyPatrol->save()) {
            // Create a new polyline and save it
            /** @var Polyline $polyline */
            $polyline = Polyline::findOrNew($enemyPatrol->polyline_id);
            $polyline->model_id = $enemyPatrol->id;
            $polyline->model_class = get_class($enemyPatrol);
            $polyline->color = $request->get('color', '#f00');
            $polyline->weight = $request->get('weight', 2);
            $polyline->vertices_json = json_encode($request->get('vertices'));
            $polyline->save();

            // Couple the patrol to the polyline
            $enemyPatrol->polyline_id = $polyline->id;

            if ($enemyPatrol->save() && Auth::check()) {
                broadcast(new ModelChangedEvent($enemyPatrol->floor->dungeon, Auth::getUser(), $enemyPatrol));
            }
        } else {
            throw new \Exception("Unable to save enemy patrol!");
        }

        return ['id' => $enemyPatrol->id];
    }

    /**
     * @param Request $request
     * @param EnemyPatrol $enemypatrol
     * @return array|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    function delete(Request $request, EnemyPatrol $enemypatrol)
    {
        try {
            if ($enemypatrol->delete()) {
                if (Auth::check()) {
                    broadcast(new ModelDeletedEvent($enemypatrol->floor->dungeon, Auth::getUser(), $enemypack));
                }
            }
            $result = response()->noContent();
        } catch (\Exception $ex) {
            $result = response('Not found', Http::NOT_FOUND);
        }

        return $result;
    }
}
