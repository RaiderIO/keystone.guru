<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\ChecksForDuplicates;
use App\Models\EnemyPatrol;
use App\Models\EnemyPatrolVertex;
use App\Models\Polyline;
use Illuminate\Http\Request;
use Teapot\StatusCode\Http;

class APIEnemyPatrolController extends Controller
{
    use ChecksForDuplicates;

    function list(Request $request)
    {
        $floorId = $request->get('floor_id');
        return EnemyPatrol::with('polyline')->where('floor_id', '=', $floorId)->get();
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
        $enemyPatrol->enemy_id = $request->get('enemy_id');
        $enemyPatrol->faction = $request->get('faction', 'any');

        // Init to a default value if new
        if (!$enemyPatrol->exists) {
            $enemyPatrol->polyline_id = -1;
        }

        if (!$enemyPatrol->save()) {
            throw new \Exception("Unable to save enemy patrol!");
        } else {
            // Create a new polyline and save it
            /** @var Polyline $polyline */
            $polyline = Polyline::findOrNew($enemyPatrol->polyline_id);
            $polyline->model_id = $enemyPatrol->id;
            $polyline->model_class = get_class($enemyPatrol);
            $polyline->color = $request->get('color');
            $polyline->weight = $request->get('weight');
            $polyline->vertices_json = json_encode($request->get('vertices'));
            $polyline->save();

            // Couple the patrol to the polyline
            $enemyPatrol->polyline_id = $polyline->id;
            $enemyPatrol->save();
        }

        return ['id' => $enemyPatrol->id];
    }

    function delete(Request $request)
    {
        try {
            /** @var EnemyPatrol $enemyPatrol */
            $enemyPatrol = EnemyPatrol::findOrFail($request->get('id'));

            $enemyPatrol->polyline->delete();
            $enemyPatrol->delete();
            $result = ['result' => 'success'];
        } catch (\Exception $ex) {
            $result = response('Not found', Http::NOT_FOUND);
        }

        return $result;
    }
}
