<?php

namespace App\Http\Controllers;

use App\Models\EnemyPack;
use App\Models\EnemyPackVertex;
use Illuminate\Http\Request;
use Teapot\StatusCode\Http;

class APIEnemyPackController extends Controller
{
    //
    function list(Request $request)
    {
        $floorId = $request->get('floor_id');
        return EnemyPack::with(['vertices' => function ($query) {
            /** @var $query \Illuminate\Database\Query\Builder */
            $query->select(['enemy_pack_id', 'x', 'y']); // must select enemy_pack_id, else it won't return results /sadface
        }])->where('floor_id', '=', $floorId)->get(['id', 'label']);
    }

    /**
     * @param Request $request
     * @return array
     * @throws \Exception
     */
    function store(Request $request)
    {
        /** @var EnemyPack $enemyPack */
        $enemyPack = EnemyPack::findOrNew($request->get('id'));

        $enemyPack->label = $request->get('label');
        $enemyPack->floor_id = $request->get('floor_id');

        if (!$enemyPack->save()) {
            throw new \Exception("Unable to save pack!");
        } else {
            $enemyPack->deleteVertices();

            // Get the new vertices
            $vertices = $request->get('vertices');
            // Store them
            foreach ($vertices as $vertex) {
                $vertexModel = new EnemyPackVertex();
                $vertexModel->enemy_pack_id = $enemyPack->id;
                $vertexModel->x = $vertex['x'];
                $vertexModel->y = $vertex['y'];

                if (!$vertexModel->save()) {
                    throw new \Exception("Unable to save pack vertex!");
                }
            }
        }

        return ['id' => $enemyPack->id];
    }

    function delete(Request $request){
        try {
            /** @var EnemyPack $enemyPack */
            $enemyPack = EnemyPack::findOrFail($request->get('id'));

            $enemyPack->deleteVertices();
            $enemyPack->delete();
            $result = ['result' => 'success'];
        } catch( \Exception $ex ){
            $result = response('Not found', Http::NOT_FOUND);
        }

        return $result;
    }
}
