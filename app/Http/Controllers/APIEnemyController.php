<?php

namespace App\Http\Controllers;

use App\Models\Npc;
use Illuminate\Http\Request;
use App\Models\Enemy;
use Teapot\StatusCode\Http;

class APIEnemyController extends Controller
{
    function list(Request $request)
    {
        $floorId = $request->get('floor_id');
        return Enemy::all()->where('floor_id', '=', $floorId);
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
        $enemy->teeming = $request->get('teeming');
        $enemy->lat = $request->get('lat');
        $enemy->lng = $request->get('lng');

        if (!$enemy->save()) {
            throw new \Exception("Unable to save enemy!");
        }

        $result = ['id' => $enemy->id];

        if( $enemy->npc_id > 0 ){
            $result['npc'] = Npc::findOrFail($enemy->npc_id);
        }

        return $result;
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
