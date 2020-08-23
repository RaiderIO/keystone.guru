<?php

namespace App\Http\Controllers;

use App\Events\ModelChangedEvent;
use App\Events\ModelDeletedEvent;
use App\Http\Controllers\Traits\ChecksForDuplicates;
use App\Http\Controllers\Traits\ListsEnemyPacks;
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
     * @return EnemyPack
     * @throws \Exception
     */
    function store(Request $request)
    {
        /** @var EnemyPack $enemyPack */
        $enemyPack = EnemyPack::findOrNew($request->get('id'));

        $enemyPack->teeming = $request->get('teeming');
        $enemyPack->faction = $request->get('faction', 'any');
        $enemyPack->label = $request->get('label');
        $enemyPack->floor_id = (int) $request->get('floor_id');
        $enemyPack->vertices_json = json_encode($request->get('vertices'));

        // Upon successful save!
        if ($enemyPack->save()) {
            if (Auth::check()) {
                broadcast(new ModelChangedEvent($enemyPack->floor->dungeon, Auth::getUser(), $enemyPack));
            }
        } else {
            throw new \Exception("Unable to save pack!");
        }

        return $enemyPack;
    }

    /**
     * @param Request $request
     * @param EnemyPack $enemypack
     * @return array|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    function delete(Request $request, EnemyPack $enemypack)
    {
        try {
            if ($enemypack->delete()) {
                if (Auth::check()) {
                    broadcast(new ModelDeletedEvent($enemypack->floor->dungeon, Auth::getUser(), $enemypack));
                }
            }
            $result = response()->noContent();
        } catch (\Exception $ex) {
            $result = response('Not found', Http::NOT_FOUND);
        }

        return $result;
    }
}
