<?php

namespace App\Http\Controllers;

use App\Events\OverpulledEnemy\OverpulledEnemyChangedEvent;
use App\Events\OverpulledEnemy\OverpulledEnemyDeletedEvent;
use App\Http\Requests\OverpulledEnemy\OverpulledEnemyFormRequest;
use App\Models\DungeonRoute;
use App\Models\Enemies\OverpulledEnemy;
use App\Models\LiveSession;
use App\Service\LiveSession\OverpulledEnemyServiceInterface;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Teapot\StatusCode\Http;

class APIOverpulledEnemyController extends Controller
{
    /**
     * @param OverpulledEnemyServiceInterface $overpulledEnemyService
     * @param OverpulledEnemyFormRequest $request
     * @param DungeonRoute $dungeonroute
     * @param LiveSession $livesession
     * @return array
     * @throws AuthorizationException
     */
    function store(
        OverpulledEnemyServiceInterface $overpulledEnemyService,
        OverpulledEnemyFormRequest      $request,
        DungeonRoute                    $dungeonroute, LiveSession $livesession)
    {
        $this->authorize('view', $dungeonroute);
        $this->authorize('view', $livesession);

        foreach ($request->get('enemy_ids', []) as $enemyId) {
            /** @var OverpulledEnemy $overpulledEnemy */
            $overpulledEnemy = OverpulledEnemy::where('live_session_id', $livesession->id)
                ->where('enemy_id', $enemyId)->firstOrNew([
                    'live_session_id' => $livesession->id,
                    'kill_zone_id'    => (int)$request->get('kill_zone_id'),
                    'enemy_id'        => $enemyId,
                ]);

            if (!$overpulledEnemy->save()) {
                throw new Exception('Unable to save overpulled enemy!');
            }

            if (Auth::check()) {
                broadcast(new OverpulledEnemyChangedEvent($livesession, Auth::getUser(), $overpulledEnemy));
            }
        }

        return $overpulledEnemyService->getRouteCorrection($livesession)->toArray();
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
        OverpulledEnemyFormRequest      $request, DungeonRoute $dungeonroute, LiveSession $livesession)
    {
        $this->authorize('view', $dungeonroute);
        $this->authorize('view', $livesession);

        try {
            $enemyIds = $request->get('enemy_ids', []);
            if (!empty($enemyIds)) {
                foreach ($enemyIds as $enemyId) {
                    /** @var OverpulledEnemy $overpulledEnemy */
                    $overpulledEnemy = OverpulledEnemy::where('live_session_id', $livesession->id)
                        ->where('enemy_id', $enemyId)->first();

                    if ($overpulledEnemy && $overpulledEnemy->delete() && Auth::check()) {
                        broadcast(new OverpulledEnemyDeletedEvent($livesession, Auth::getUser(), $overpulledEnemy));
                    }

                    // Optionally don't calculate the return value
                    $result = $request->get('no_result', false) ? response()->noContent() : $overpulledEnemyService->getRouteCorrection($livesession)->toArray();
                }
            } else {
                $result = response()->noContent();
            }
        } catch (Exception $ex) {
            $result = response('Not found', Http::NOT_FOUND);
        }

        return $result;
    }
}
