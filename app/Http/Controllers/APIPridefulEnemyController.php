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
     * @param DungeonRoute $dungeonRoute
     * @param Enemy $enemy
     * @return PridefulEnemy
     * @throws Exception
     */
    function store(Request $request, DungeonRoute $dungeonRoute, Enemy $enemy)
    {
        if (!$dungeonRoute->isSandbox()) {
            $this->authorize('edit', $dungeonRoute);
        }

        /** @var PridefulEnemy $pridefulEnemy */
        $pridefulEnemy = PridefulEnemy::where('dungeon_route_id', $dungeonRoute->id)->where('enemy_id', $enemy->id)->first();

        if ($pridefulEnemy === null) {
            $pridefulEnemy = new PridefulEnemy();
        }

        $pridefulEnemy->dungeon_route_id = $dungeonRoute->id;
        $pridefulEnemy->enemy_id         = (int)$enemy->id;
        $pridefulEnemy->floor_id         = (int)$request->get('floor_id');
        $pridefulEnemy->lat              = (float)$request->get('lat');
        $pridefulEnemy->lng              = (float)$request->get('lng');

        if (!$pridefulEnemy->save()) {
            throw new Exception('Unable to save prideful enemy!');
        }

        if (Auth::check()) {
            broadcast(new ModelChangedEvent($dungeonRoute, Auth::getUser(), $pridefulEnemy));
        }

        $dungeonRoute->touch();

        return $pridefulEnemy;
    }

    /**
     * @param Request $request
     * @param DungeonRoute $dungeonRoute
     * @param Enemy $enemy
     * @return Response|ResponseFactory
     */
    function delete(Request $request, DungeonRoute $dungeonRoute, Enemy $enemy)
    {
        try {
            /** @var PridefulEnemy $pridefulEnemy */
            $pridefulEnemy = PridefulEnemy::where('dungeon_route_id', $dungeonRoute->id)->where('enemy_id', $enemy->id)->first();
            if ($pridefulEnemy && $pridefulEnemy->delete() && Auth::check()) {
                broadcast(new ModelDeletedEvent($dungeonRoute, Auth::getUser(), $pridefulEnemy));
            }

            $dungeonRoute->touch();

            $result = response()->noContent();
        } catch (Exception $ex) {
            $result = response('Not found', Http::NOT_FOUND);
        }

        return $result;
    }
}
