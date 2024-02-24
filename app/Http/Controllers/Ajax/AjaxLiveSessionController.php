<?php

namespace App\Http\Controllers\Ajax;

use App\Events\LiveSession\StopEvent;
use App\Http\Controllers\Controller;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\LiveSession;
use Exception;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Teapot\StatusCode\Http;

class AjaxLiveSessionController extends Controller
{
    /**
     * @return Response|ResponseFactory
     */
    function delete(Request $request, DungeonRoute $dungeonRoute, LiveSession $liveSession)
    {
        try {
            if ($liveSession->expires_at === null) {
                $expiresHours = config('keystoneguru.live_sessions.expires_hours');

                $liveSession->expires_at = now()->addHours($expiresHours);
                $liveSession->save();

                if (Auth::check()) {
                    broadcast(new StopEvent($liveSession, Auth::user()));
                }

                // Convert to seconds
                $result = ['expires_in' => $expiresHours * 3600];
            } else {
                $result = ['expires_in' => $liveSession->getExpiresInSeconds()];
            }

        } catch (Exception) {
            $result = response(__('controller.generic.error.not_found'), Http::NOT_FOUND);
        }

        return $result;
    }
}
