<?php

namespace App\Http\Controllers;

use App\Events\LiveSession\StopEvent;
use App\Models\DungeonRoute;
use App\Models\LiveSession;
use Exception;
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
     * @param LiveSession $livesession
     * @return Response|ResponseFactory
     */
    function delete(Request $request, DungeonRoute $dungeonroute, LiveSession $livesession)
    {
        try {
            if ($livesession->expires_at === null) {
                $expiresHours = config('keystoneguru.live_sessions.expires_hours');

                $livesession->expires_at = now()->addHours($expiresHours);
                $livesession->save();

                if (Auth::check()) {
                    broadcast(new StopEvent($livesession, Auth::user()));
                }

                // Convert to seconds
                $result = ['expires_in' => $expiresHours * 3600];
            } else {
                $result = ['expires_in' => $livesession->getExpiresInSeconds()];
            }

        } catch (Exception $ex) {
            $result = response('Not found', Http::NOT_FOUND);
        }

        return $result;
    }
}
