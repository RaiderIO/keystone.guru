<?php

namespace App\Http\Controllers;

use App\Events\OverpulledEnemy\OverpulledEnemyChangedEvent;
use App\Events\OverpulledEnemy\OverpulledEnemyDeletedEvent;
use App\Models\DungeonRoute;
use App\Models\Enemies\OverpulledEnemy;
use App\Models\Enemy;
use App\Models\LiveSession;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Teapot\StatusCode\Http;

class APIOverpulledEnemyController extends Controller
{
    /**
     * @param Request $request
     * @param DungeonRoute $dungeonroute
     * @param LiveSession $livesession
     * @param Enemy $enemy
     * @return OverpulledEnemy
     * @throws AuthorizationException
     */
    function store(Request $request, DungeonRoute $dungeonroute, LiveSession $livesession, Enemy $enemy)
    {
        $this->authorize('view', $dungeonroute);
        $this->authorize('view', $livesession);

        /** @var OverpulledEnemy $overpulledEnemy */
        $overpulledEnemy = OverpulledEnemy::where('live_session_id', $livesession->id)
            ->where('enemy_id', $enemy->id)->firstOrNew([
                'live_session_id' => $livesession->id,
                'enemy_id'        => $enemy->id
            ]);

        if (!$overpulledEnemy->save()) {
            throw new Exception('Unable to save overpulled enemy!');
        }

        if (Auth::check()) {
            broadcast(new OverpulledEnemyChangedEvent($livesession, Auth::getUser(), $overpulledEnemy));
        }

        return $overpulledEnemy;
    }

    /**
     * @param Request $request
     * @param DungeonRoute $dungeonroute
     * @param LiveSession $livesession
     * @param Enemy $enemy
     * @return array|ResponseFactory|Response
     * @throws AuthorizationException
     */
    function delete(Request $request, DungeonRoute $dungeonroute, LiveSession $livesession, Enemy $enemy)
    {
        $this->authorize('view', $dungeonroute);
        $this->authorize('view', $livesession);

        try {
            /** @var OverpulledEnemy $overpulledEnemy */
            $overpulledEnemy = OverpulledEnemy::where('live_session_id', $livesession->id)
                ->where('enemy_id', $enemy->id)->first();

            if ($overpulledEnemy->delete() && Auth::check()) {
                broadcast(new OverpulledEnemyDeletedEvent($livesession, Auth::getUser(), $overpulledEnemy));
            }

            $result = response()->noContent();
        } catch (Exception $ex) {
            $result = response('Not found', Http::NOT_FOUND);
        }

        return $result;
    }
}
