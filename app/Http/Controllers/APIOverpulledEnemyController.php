<?php

namespace App\Http\Controllers;

use App\Events\OverpulledEnemy\OverpulledEnemyChangedEvent;
use App\Events\OverpulledEnemy\OverpulledEnemyDeletedEvent;
use App\Http\Requests\OverpulledEnemy\OverpulledEnemyFormRequest;
use App\Models\DungeonRoute;
use App\Models\Enemies\OverpulledEnemy;
use App\Models\Enemy;
use App\Models\LiveSession;
use App\Service\LiveSession\OverpulledEnemyServiceInterface;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Teapot\StatusCode\Http;

class APIOverpulledEnemyController extends Controller
{
    /**
     * @param OverpulledEnemyServiceInterface $overpulledEnemyService
     * @param OverpulledEnemyFormRequest $request
     * @param DungeonRoute $dungeonRoute
     * @param LiveSession $liveSession
     * @return array
     * @throws AuthorizationException
     */
    function store(
        OverpulledEnemyServiceInterface $overpulledEnemyService,
        OverpulledEnemyFormRequest      $request,
        DungeonRoute                    $dungeonRoute,
        LiveSession                     $liveSession)
    {
        $this->authorize('view', $dungeonRoute);
        $this->authorize('view', $liveSession);

        $validated = $request->validated();

        /** @var Collection|Enemy[] $enemies */
        $enemies = Enemy::whereIn('id', $validated['enemy_ids'])->get();

        foreach ($enemies as $enemy) {
            /** @var OverpulledEnemy $overpulledEnemy */
            $overpulledEnemy = OverpulledEnemy::where('live_session_id', $liveSession->id)
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
                broadcast(new OverpulledEnemyChangedEvent($liveSession, Auth::getUser(), $overpulledEnemy, $enemy));
            }
        }

        return $overpulledEnemyService->getRouteCorrection($liveSession)->toArray();
    }

    /**
     * @param OverpulledEnemyServiceInterface $overpulledEnemyService
     * @param OverpulledEnemyFormRequest $request
     * @param DungeonRoute $dungeonroute
     * @param LiveSession $livesession
     * @return array|ResponseFactory|Response
     * @throws AuthorizationException
     */
    function delete(
        OverpulledEnemyServiceInterface $overpulledEnemyService,
        OverpulledEnemyFormRequest      $request,
        DungeonRoute                    $dungeonroute,
        LiveSession                     $livesession)
    {
        $this->authorize('view', $dungeonroute);
        $this->authorize('view', $livesession);

        $result = response()->noContent();

        $validated = $request->validated();

        /** @var Collection|Enemy[] $enemies */
        $enemies = Enemy::whereIn('id', $validated['enemy_ids'])->get();

        try {
            foreach ($enemies as $enemy) {
                /** @var OverpulledEnemy $overpulledEnemy */
                $overpulledEnemy = OverpulledEnemy::where('live_session_id', $livesession->id)
                    ->where('npc_id', $enemy->npc_id)
                    ->where('mdt_id', $enemy->mdt_id)
                    ->first();

                if ($overpulledEnemy && $overpulledEnemy->delete() && Auth::check()) {
                    broadcast(new OverpulledEnemyDeletedEvent($livesession, Auth::getUser(), $overpulledEnemy));
                }

                // Optionally don't calculate the return value
                $result = $validated['no_result'] === true ? $result : $overpulledEnemyService->getRouteCorrection($livesession)->toArray();
            }
        } catch (Exception $ex) {
            $result = response('Not found', Http::NOT_FOUND);
        }

        return $result;
    }
}
