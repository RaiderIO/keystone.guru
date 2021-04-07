<?php

namespace App\Http\Controllers;

use App\Events\ModelChangedEvent;
use App\Events\ModelDeletedEvent;
use App\Http\Controllers\Traits\ChangesMapping;
use App\Http\Controllers\Traits\ChecksForDuplicates;
use App\Http\Controllers\Traits\ListsEnemyPacks;
use App\Models\EnemyPack;
use Exception;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Teapot\StatusCode\Http;

class APIEnemyPackController extends Controller
{
    use ChangesMapping;
    use ChecksForDuplicates;

    /**
     * @param Request $request
     * @return EnemyPack
     * @throws Exception
     */
    function store(Request $request)
    {
        /** @var EnemyPack $enemyPack */
        $enemyPack = EnemyPack::findOrNew($request->get('id'));

        $beforeEnemyPack = clone $enemyPack;

        $enemyPack->teeming = $request->get('teeming');
        $enemyPack->faction = $request->get('faction', 'any');
        $enemyPack->label = $request->get('label');
        $enemyPack->floor_id = (int)$request->get('floor_id');
        $enemyPack->vertices_json = json_encode($request->get('vertices'));

        // Upon successful save!
        if ($enemyPack->save()) {
            if (Auth::check()) {
                broadcast(new ModelChangedEvent($enemyPack->floor->dungeon, Auth::getUser(), $enemyPack));
            }

            // Trigger mapping changed event so the mapping gets saved across all environments
            $this->mappingChanged($beforeEnemyPack, $enemyPack);
        } else {
            throw new Exception('Unable to save pack!');
        }

        return $enemyPack;
    }

    /**
     * @param Request $request
     * @param EnemyPack $enemypack
     * @return array|ResponseFactory|Response
     */
    function delete(Request $request, EnemyPack $enemypack)
    {
        try {
            if ($enemypack->delete()) {
                if (Auth::check()) {
                    broadcast(new ModelDeletedEvent($enemypack->floor->dungeon, Auth::getUser(), $enemypack));
                }

                // Trigger mapping changed event so the mapping gets saved across all environments
                $this->mappingChanged($enemypack, null);
            }
            $result = response()->noContent();
        } catch (Exception $ex) {
            $result = response('Not found', Http::NOT_FOUND);
        }

        return $result;
    }
}
