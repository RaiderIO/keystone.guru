<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Enemy;
use Teapot\StatusCode\Http;

class EnemyController extends Controller
{
    //
    function list(Request $request)
    {
        $floorId = $request->get('floor_id');
        return Enemy::all()->/*with(['vertices'])->*/
        where('floor_id', '=', $floorId)->get(['id', 'label']);
    }

    /**
     * @param Request $request
     * @return array
     * @throws \Exception
     */
    function store(Request $request)
    {
        /** @var Enemy $enemy */
        $enemy = Enemy::findOrNew($request->get('id'));

        $enemy->enemy_pack_id = $request->get('enemy_pack_id');
        $enemy->npc_id = $request->get('npc_id');
        $enemy->floor_id = $request->get('floor_id');
        $enemy->x = $request->get('x');
        $enemy->y = $request->get('y');

        if (!$enemy->save()) {
            throw new \Exception("Unable to save enemy!");
        }

        return ['id' => $enemy->id];
    }

    function delete(Request $request)
    {
        try {
            /** @var Enemy $enemy */
            $enemy = Enemy::findOrFail($request->get('id'));

            $enemy->delete();
            $result = ['result' => 'success'];
        } catch (\Exception $ex) {
            $result = response('Not found', Http::NOT_FOUND);
        }

        return $result;
    }
}
