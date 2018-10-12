<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\ChecksForDuplicates;
use App\Models\EnemyPack;
use App\Models\EnemyPackVertex;
use Illuminate\Http\Request;
use Teapot\StatusCode\Http;

class APIEnemyPackController extends Controller
{
    use ChecksForDuplicates;

    //
    function list(Request $request)
    {
        $floorId = $request->get('floor_id');
        return EnemyPack::with(['vertices' => function ($query) {
            /** @var $query \Illuminate\Database\Query\Builder */
            $query->select(['enemy_pack_id', 'lat', 'lng']); // must select enemy_pack_id, else it won't return results /sadface
        }])->where('floor_id', '=', $floorId)->get(['id', 'label', 'faction']);
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

        $enemyPack->faction = $request->get('faction', 'any');
        $enemyPack->label = $request->get('label');
        $enemyPack->floor_id = $request->get('floor_id');

        if (!$enemyPack->save()) {
            throw new \Exception("Unable to save pack!");
        } else {
            $enemyPack->deleteVertices();

            // Get the new vertices
            $vertices = $request->get('vertices');

            // Store them
            foreach ($vertices as $key => $vertex) {
                // Assign route to each passed vertex
                $vertices[$key]['enemy_pack_id'] = $enemyPack->id;
            }

            $this->checkForDuplicateVertices('App\Models\EnemyPackVertex', $vertices);

            // Bulk insert
            EnemyPackVertex::insert($vertices);
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
