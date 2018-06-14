<?php

namespace App\Http\Controllers;

use App\Models\EnemyPack;
use App\Models\EnemyPackVertex;
use Illuminate\Http\Request;
use App\Models\Floor;
use Mockery\Exception;

class EnemyPackController extends Controller
{
    //
    function list()
    {
        return Floor::all()->load(['enemypacks', 'enemypacks.vertices']);
    }

    function store(Request $request)
    {
        /** @var EnemyPack $enemyPack */
        $enemyPack = EnemyPack::findOrNew($request->get('id'));

        $enemyPack->label = $request->get('label');
        $enemyPack->floor_id = $request->get('floor_id');

        if (!$enemyPack->save()) {
            throw new Exception("Unable to save pack!");
        } else {
            // Load the existing vertices from the pack
            $existingVerticesIds = $enemyPack->vertices->pluck('id')->all();
            // Only if there's vertices to destroy
            if(count($existingVerticesIds) > 0){
                // Kill them off
                EnemyPackVertex::destroy($existingVerticesIds);
            }

            // Get the new vertices
            $vertices = $request->get('vertices');
            // Store them
            foreach ($vertices as $vertex) {
                $vertexModel = new EnemyPackVertex();
                $vertexModel->enemy_pack_id = $enemyPack->id;
                $vertexModel->x = $vertex['x'];
                $vertexModel->y = $vertex['y'];

                if (!$vertexModel->save()) {
                    throw new Exception("Unable to save pack vertex!");
                }
            }
        }

        return ['result' => 'success', 'id' => $enemyPack->id];
    }
}
