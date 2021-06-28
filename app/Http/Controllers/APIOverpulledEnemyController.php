<?php

namespace App\Http\Controllers;

use App\Events\Model\ModelChangedEvent;
use App\Events\Model\ModelDeletedEvent;
use App\Models\Enemies\OverpulledEnemy;
use App\Models\Enemy;
use App\Models\LiveSession;
use Exception;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Teapot\StatusCode\Http;

class APIOverpulledEnemyController extends Controller
{
    /**
     * @param Request $request
     * @param LiveSession $livesession
     * @param Enemy $enemy
     * @return OverpulledEnemy
     * @throws Exception
     */
    function store(Request $request, LiveSession $livesession, Enemy $enemy)
    {
        $this->authorize('view', $livesession);

        /** @var OverpulledEnemy $overpulledEnemy */
        $overpulledEnemy = OverpulledEnemy::where('live_session_id', $livesession->id)
            ->where('enemy_id', $enemy->id)->firstOrNew([
                'live_session_id' => $livesession->id,
                'enemy_id'        => (int)$request->get('enemy_id')
            ]);

        if (!$overpulledEnemy->save()) {
            throw new Exception('Unable to save overpulled enemy!');
        }

        if (Auth::check()) {
            broadcast(new ModelChangedEvent($livesession->dungeonroute, Auth::getUser(), $overpulledEnemy));
        }

        return $overpulledEnemy;
    }

    /**
     * @param Request $request
     * @param LiveSession $livesession
     * @param Enemy $enemy
     * @return array|ResponseFactory|Response
     */
    function delete(Request $request, LiveSession $livesession, Enemy $enemy)
    {
        try {
            /** @var OverpulledEnemy $overpulledEnemy */
            $overpulledEnemy = OverpulledEnemy::where('live_session_id', $livesession->id)
                ->where('enemy_id', $enemy->id)->first();

            if ($overpulledEnemy->delete() && Auth::check()) {
                broadcast(new ModelDeletedEvent($livesession->dungeonroute, Auth::getUser(), $overpulledEnemy));
            }

            $result = response()->noContent();
        } catch (Exception $ex) {
            $result = response('Not found', Http::NOT_FOUND);
        }

        return $result;
    }
}
