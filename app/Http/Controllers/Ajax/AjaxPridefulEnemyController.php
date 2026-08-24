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
use Illuminate\Broadcasting\BroadcastException;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Teapot\StatusCode\Http;

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

        /** @var PridefulEnemy|null $pridefulEnemy */
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

        // The save and the route touch that refreshes its thumbnail have to land together, or the
        // route keeps advertising a thumbnail that no longer shows the prideful enemy on it
        DB::transaction(function () use ($dungeonRoute, $pridefulEnemy): void {
            if (!$pridefulEnemy->save()) {
                throw new Exception('Unable to save prideful enemy!');
            }

            $dungeonRoute->touch();
        });

        // Broadcast only once the save is committed, so no listener can read pre-commit state
        if (Auth::check()) {
            /** @var User $user */
            $user = Auth::getUser();

            try {
                broadcast(new PridefulEnemyChangedEvent($coordinatesService, $dungeonRoute, $user, $pridefulEnemy));
            } catch (BroadcastException) {
                // Ignore broadcast failures
            }
        }

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
            /** @var PridefulEnemy|null $pridefulEnemy */
            $pridefulEnemy = PridefulEnemy::where('dungeon_route_id', $dungeonRoute->id)->where('enemy_id', $enemy->id)->first();

            // The delete and the route touch that refreshes its thumbnail have to land together, or
            // the route keeps advertising a thumbnail that still shows the prideful enemy on it
            $deleted = DB::transaction(function () use ($dungeonRoute, $pridefulEnemy): bool {
                $result = $pridefulEnemy !== null && $pridefulEnemy->delete() === true;

                $dungeonRoute->touch();

                return $result;
            });

            // Broadcast only once the delete is committed, so no listener can read pre-commit state
            if ($deleted && Auth::check()) {
                /** @var User $user */
                $user = Auth::getUser();

                try {
                    broadcast(new PridefulEnemyDeletedEvent($dungeonRoute, $user, $pridefulEnemy));
                } catch (BroadcastException) {
                    // Ignore broadcast failures
                }
            }

            $result = response()->noContent();
        } catch (Exception) {
            $result = response(__('controller.generic.error.not_found'), Http::NOT_FOUND);
        }

        return $result;
    }
}
