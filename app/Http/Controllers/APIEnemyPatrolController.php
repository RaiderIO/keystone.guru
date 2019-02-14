<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\ChecksForDuplicates;
use App\Models\EnemyPatrol;
use App\Models\EnemyPatrolVertex;
use Illuminate\Http\Request;
use Teapot\StatusCode\Http;

class APIEnemyPatrolController extends Controller
{
    use ChecksForDuplicates;

    function list(Request $request)
    {
        $floorId = $request->get('floor_id');
        return EnemyPatrol::all()->where('floor_id', '=', $floorId);
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
        $enemyPatrol->vertices_json = json_encode($request->get('vertices'));

        if (!$enemyPatrol->save()) {
            throw new \Exception("Unable to save enemy patrol!");
        }

        return ['id' => $enemyPatrol->id];
    }

    function delete(Request $request)
    {
        try {
            /** @var EnemyPatrol $enemyPatrol */
            $enemyPatrol = EnemyPatrol::findOrFail($request->get('id'));

            $enemyPatrol->delete();
            $result = ['result' => 'success'];
        } catch (\Exception $ex) {
            $result = response('Not found', Http::NOT_FOUND);
        }

        return $result;
    }
}
