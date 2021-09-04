<?php

namespace App\Http\Controllers;

use App\Events\Model\ModelChangedEvent;
use App\Events\Model\ModelDeletedEvent;
use App\Models\DungeonRoute;
use App\Models\Enemies\PridefulEnemy;
use App\Models\Enemy;
use Exception;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Teapot\StatusCode\Http;

class APIPridefulEnemyController extends Controller
{
    /**
     * @param Request $request
     * @param DungeonRoute $dungeonroute
     * @param Enemy $enemy
     * @return PridefulEnemy
     * @throws Exception
     */
    function store(Request $request, DungeonRoute $dungeonroute, Enemy $enemy)
    {
        if (!$dungeonroute->isSandbox()) {
            $this->authorize('edit', $dungeonroute);
        }

        /** @var PridefulEnemy $pridefulEnemy */
        $pridefulEnemy = PridefulEnemy::where('dungeon_route_id', $dungeonroute->id)->where('enemy_id', $enemy->id)->first();

        if ($pridefulEnemy === null) {
            $pridefulEnemy = new PridefulEnemy();
        }

        $pridefulEnemy->dungeon_route_id = $dungeonroute->id;
        $pridefulEnemy->enemy_id         = (int)$enemy->id;
        $pridefulEnemy->floor_id         = (int)$request->get('floor_id');
        $pridefulEnemy->lat              = (float)$request->get('lat');
        $pridefulEnemy->lng              = (float)$request->get('lng');

        if (!$pridefulEnemy->save()) {
            throw new Exception('Unable to save prideful enemy!');
        }

        if (Auth::check()) {
            broadcast(new ModelChangedEvent($dungeonroute, Auth::getUser(), $pridefulEnemy));
        }

        $dungeonroute->touch();

        return $pridefulEnemy;
    }

    /**
     * @param Request $request
     * @param DungeonRoute $dungeonroute
     * @param Enemy $enemy
     * @return Response|ResponseFactory
     */
    function delete(Request $request, DungeonRoute $dungeonroute, Enemy $enemy)
    {
        try {
            /** @var PridefulEnemy $pridefulEnemy */
            $pridefulEnemy = PridefulEnemy::where('dungeon_route_id', $dungeonroute->id)->where('enemy_id', $enemy->id)->first();
            if ($pridefulEnemy && $pridefulEnemy->delete() && Auth::check()) {
                broadcast(new ModelDeletedEvent($dungeonroute, Auth::getUser(), $pridefulEnemy));
            }

            $dungeonroute->touch();

            $result = response()->noContent();
        } catch (Exception $ex) {
            $result = response('Not found', Http::NOT_FOUND);
        }

        return $result;
    }
}
