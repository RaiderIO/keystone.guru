<?php

namespace App\Http\Controllers;

use App\Models\EnemyPatrol;
use App\Models\EnemyPatrolVertex;
use Illuminate\Http\Request;
use Teapot\StatusCode\Http;

class APIEnemyPatrolController extends Controller
{
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

        if (!$enemyPatrol->save()) {
            throw new \Exception("Unable to save enemy patrol!");
        } else {
            $enemyPatrol->deleteVertices();

            // Get the new vertices
            $vertices = $request->get('vertices');
            // Store them
            foreach ($vertices as $vertex) {
                $vertexModel = new EnemyPatrolVertex();
                $vertexModel->enemy_patrol_id = $enemyPatrol->id;
                $vertexModel->lat = $vertex['lat'];
                $vertexModel->lng = $vertex['lng'];

                if (!$vertexModel->save()) {
                    throw new \Exception("Unable to save pack vertex!");
                }
            }
        }

        return ['id' => $enemyPatrol->id];
    }

    function delete(Request $request)
    {
        try {
            /** @var EnemyPatrol $enemyPatrol */
            $enemyPatrol = EnemyPatrol::findOrFail($request->get('id'));

            $enemyPatrol->delete();
            $enemyPatrol->deleteVertices();
            $result = ['result' => 'success'];
        } catch (\Exception $ex) {
            $result = response('Not found', Http::NOT_FOUND);
        }

        return $result;
    }
}
