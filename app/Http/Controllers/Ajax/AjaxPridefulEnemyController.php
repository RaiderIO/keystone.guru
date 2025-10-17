<?php

namespace App\Http\Controllers\Ajax;

use App\Events\Models\PridefulEnemy\PridefulEnemyChangedEvent;
use App\Events\Models\PridefulEnemy\PridefulEnemyDeletedEvent;
use App\Http\Controllers\Controller;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Enemies\PridefulEnemy;
use App\Models\Enemy;
use App\Models\User;
use App\Service\Coordinates\CoordinatesServiceInterface;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Teapot\StatusCode\Http;
use Illuminate\Support\Facades\Gate;

class AjaxPridefulEnemyController extends Controller
{
    /**
     * @throws Exception
     */
    public function store(
        Request                     $request,
        CoordinatesServiceInterface $coordinatesService,
        DungeonRoute                $dungeonRoute,
        Enemy                       $enemy,
    ): PridefulEnemy {
        Gate::authorize('edit', $dungeonRoute);

        /** @var PridefulEnemy $pridefulEnemy */
        $pridefulEnemy = PridefulEnemy::where('dungeon_route_id', $dungeonRoute->id)->where('enemy_id', $enemy->id)->first();

        if ($pridefulEnemy === null) {
            $pridefulEnemy = new PridefulEnemy();
        }

        $pridefulEnemy->dungeon_route_id = $dungeonRoute->id;
        $pridefulEnemy->enemy_id         = $enemy->id;
        // @TODO support facades? Idk it's all legacy at this point - the dungeons that have Prideful enemies are all not supported by facade anyway
        $pridefulEnemy->floor_id = (int)$request->get('floor_id');
        $pridefulEnemy->lat      = (float)$request->get('lat');
        $pridefulEnemy->lng      = (float)$request->get('lng');

        if (!$pridefulEnemy->save()) {
            throw new Exception('Unable to save prideful enemy!');
        }

        if (Auth::check()) {
            /** @var User $user */
            $user = Auth::getUser();
            broadcast(new PridefulEnemyChangedEvent($coordinatesService, $dungeonRoute, $user, $pridefulEnemy));
        }

        $dungeonRoute->touch();

        return $pridefulEnemy;
    }

    /**
     * @return Response|ResponseFactory
     *
     * @throws AuthorizationException
     */
    public function delete(Request $request, DungeonRoute $dungeonRoute, Enemy $enemy)
    {
        Gate::authorize('edit', $dungeonRoute);

        try {
            /** @var PridefulEnemy $pridefulEnemy */
            $pridefulEnemy = PridefulEnemy::where('dungeon_route_id', $dungeonRoute->id)->where('enemy_id', $enemy->id)->first();
            if ($pridefulEnemy && $pridefulEnemy->delete() && Auth::check()) {
                /** @var User $user */
                $user = Auth::getUser();
                broadcast(new PridefulEnemyDeletedEvent($dungeonRoute, $user, $pridefulEnemy));
            }

            $dungeonRoute->touch();

            $result = response()->noContent();
        } catch (Exception) {
            $result = response(__('controller.generic.error.not_found'), Http::NOT_FOUND);
        }

        return $result;
    }
}
