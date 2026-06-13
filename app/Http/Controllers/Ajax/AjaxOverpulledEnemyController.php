<?php

namespace App\Http\Controllers\Ajax;

use App\Events\LiveSession\OverpulledEnemy\OverpulledEnemyChangedEvent;
use App\Events\LiveSession\OverpulledEnemy\OverpulledEnemyDeletedEvent;
use App\Events\LiveSession\RouteCorrectionEvent;
use App\Http\Controllers\Controller;
use App\Http\Requests\OverpulledEnemy\OverpulledEnemyFormRequest;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Enemy;
use App\Models\LiveSession\LiveSession;
use App\Models\LiveSession\LiveSessionOverpulledEnemy;
use App\Models\User;
use App\Service\LiveSession\LiveSessionCombatStateServiceInterface;
use App\Service\LiveSession\OverpulledEnemyServiceInterface;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Teapot\StatusCode\Http;

class AjaxOverpulledEnemyController extends Controller
{
    /**
     * @return array
     *
     * @throws AuthorizationException
     */
    public function store(
        OverpulledEnemyServiceInterface        $overpulledEnemyService,
        LiveSessionCombatStateServiceInterface $combatStateService,
        OverpulledEnemyFormRequest             $request,
        DungeonRoute                           $dungeonRoute,
        LiveSession                            $liveSession,
    ) {
        Gate::authorize('view', $dungeonRoute);
        Gate::authorize('view', $liveSession);

        $validated = $request->validated();

        /** @var Collection<Enemy> $enemies */
        $enemies = Enemy::whereIn('id', $validated['enemy_ids'])->get();

        foreach ($enemies as $enemy) {
            /** @var LiveSessionOverpulledEnemy $overpulledEnemy */
            $overpulledEnemy = LiveSessionOverpulledEnemy::where('live_session_id', $liveSession->id)
                ->where('npc_id', $enemy->npc_id)
                ->where('mdt_id', $enemy->mdt_id)
                ->firstOrNew([
                    'live_session_id' => $liveSession->id,
                    'kill_zone_id'    => $validated['kill_zone_id'],
                    'npc_id'          => $enemy->npc_id,
                    'mdt_id'          => $enemy->mdt_id,
                ]);

            if (!$overpulledEnemy->save()) {
                throw new Exception('Unable to save overpulled enemy!');
            }

            if (Auth::check()) {
                /** @var User $user */
                $user = Auth::getUser();
                broadcast(new OverpulledEnemyChangedEvent($liveSession, $user, $overpulledEnemy, $enemy));
            }
        }

        $routeCorrection = $overpulledEnemyService->getRouteCorrection($liveSession);

        if (Auth::check()) {
            /** @var User $user */
            $user     = Auth::getUser();
            $enemyIds = $routeCorrection->getObsoleteEnemies()
                ->merge($combatStateService->getObsoleteEnemyIds($liveSession))
                ->unique()
                ->values()
                ->toArray();
            broadcast(new RouteCorrectionEvent($liveSession, $user, $enemyIds));
        }

        return $routeCorrection->toArray();
    }

    /**
     * @return array|ResponseFactory|Response
     *
     * @throws AuthorizationException
     */
    public function delete(
        OverpulledEnemyServiceInterface        $overpulledEnemyService,
        LiveSessionCombatStateServiceInterface $combatStateService,
        OverpulledEnemyFormRequest             $request,
        DungeonRoute                           $dungeonRoute,
        LiveSession                            $liveSession,
    ) {
        Gate::authorize('view', $dungeonRoute);
        Gate::authorize('view', $liveSession);

        $result = response()->noContent();

        $validated = $request->validated();

        /** @var Collection<Enemy> $enemies */
        $enemies = Enemy::whereIn('id', $validated['enemy_ids'])->get();

        try {
            foreach ($enemies as $enemy) {
                /** @var LiveSessionOverpulledEnemy $overpulledEnemy */
                $overpulledEnemy = LiveSessionOverpulledEnemy::where('live_session_id', $liveSession->id)
                    ->where('npc_id', $enemy->npc_id)
                    ->where('mdt_id', $enemy->mdt_id)
                    ->first();

                if ($overpulledEnemy && $overpulledEnemy->delete() && Auth::check()) { // @phpstan-ignore booleanAnd.leftAlwaysTrue
                    /** @var User $user */
                    $user = Auth::getUser();
                    broadcast(new OverpulledEnemyDeletedEvent($liveSession, $user, $enemy));
                }

                // Optionally, don't calculate the return value
                if ($validated['no_result'] !== true) {
                    $routeCorrection = $overpulledEnemyService->getRouteCorrection($liveSession);
                    $result          = $routeCorrection->toArray();

                    if (Auth::check()) {
                        /** @var User $user */
                        $user     = Auth::getUser();
                        $enemyIds = $routeCorrection->getObsoleteEnemies()
                            ->merge($combatStateService->getObsoleteEnemyIds($liveSession))
                            ->unique()
                            ->values()
                            ->toArray();
                        broadcast(new RouteCorrectionEvent($liveSession, $user, $enemyIds));
                    }
                }
            }
        } catch (Exception) {
            $result = response(__('controller.generic.error.not_found'), Http::NOT_FOUND);
        }

        return $result;
    }
}
