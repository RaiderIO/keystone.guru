<?php

namespace App\Http\Controllers;

use App\Events\LiveSession\StopEvent;
use App\Models\DungeonRoute;
use App\Models\LiveSession;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Teapot\StatusCode\Http;

class APILiveSessionController extends Controller
{
    /**
     * @param Request $request
     * @param DungeonRoute $dungeonroute
     * @param LiveSession $liveSession
     * @return array|ResponseFactory|Response
     * @throws AuthorizationException
     */
    function delete(Request $request, DungeonRoute $dungeonroute, LiveSession $liveSession)
    {
        try {
            if ($liveSession->expires_at === null) {

                $liveSession->expires_at = now()->addHours(config('keystoneguru.live_sessions.expires_hours'));
                $liveSession->save();

                if (Auth::check()) {
                    broadcast(new StopEvent($liveSession, Auth::user()));
                }
            }

            $result = response()->noContent();
        } catch (Exception $ex) {
            $result = response('Not found', Http::NOT_FOUND);
        }

        return $result;
    }
}
