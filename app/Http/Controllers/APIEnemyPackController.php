<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\ChecksForDuplicates;
use App\Http\Controllers\Traits\ListsEnemyPacks;
use App\Models\Enemy;
use App\Models\EnemyPack;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Teapot\StatusCode\Http;

class APIEnemyPackController extends Controller
{
    use ChecksForDuplicates;
    use ListsEnemyPacks;

    //
    function list(Request $request)
    {
        $floorId = $request->get('floor_id');
        $enemies = $request->get('enemies', true);
        $teeming = $request->get('teeming', false);

        // If logged in, and we're NOT an admin
        if (Auth::check() && !Auth::user()->hasRole('admin')) {
            // Don't expose vertices
            $enemies = true;
        }


        return $this->listEnemyPacks($floorId, $enemies, $teeming);
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

        $enemyPack->teeming = $request->get('teeming');
        $enemyPack->faction = $request->get('faction', 'any');
        $enemyPack->label = $request->get('label');
        $enemyPack->floor_id = $request->get('floor_id');
        $enemyPack->vertices_json = json_encode($request->get('vertices'));

        // Upon successful save!
        if (!$enemyPack->save()) {
            throw new \Exception("Unable to save pack!");
        }

        return ['id' => $enemyPack->id];
    }

    /**
     * @param Request $request
     * @param EnemyPack $enemypack
     * @return array|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    function delete(Request $request, EnemyPack $enemypack)
    {
        try {
            $enemypack->delete();
            $result = response()->noContent();
        } catch (\Exception $ex) {
            $result = response('Not found', Http::NOT_FOUND);
        }

        return $result;
    }
}
