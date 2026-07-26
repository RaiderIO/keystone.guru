<?php

namespace App\Http\Controllers\Ajax;

use App\Events\LiveSession\StopEvent;
use App\Http\Controllers\Controller;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\LiveSession;
use App\Models\User;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Teapot\StatusCode\Http;

class AjaxLiveSessionController extends Controller
{
    /**
     * @return Response|ResponseFactory
     *
     * @throws AuthorizationException
     */
    public function delete(Request $request, DungeonRoute $dungeonRoute, LiveSession $liveSession)
    {
        // Prefer the live session's own route over the one in the URL, so that passing an unrelated
        // route cannot be used to authorize against. Ending a session is an edit of that route.
        $dungeonRoute = $liveSession->dungeonRoute ?? $dungeonRoute;

        // The session's own creator may always end it, even if they aren't the route's
        // owner/collaborator - otherwise a session started by e.g. a guest viewer can never expire.
        if ((int)$liveSession->user_id !== (int)Auth::id()) {
            Gate::authorize('edit', $dungeonRoute);
        }

        try {
            if ($liveSession->expires_at === null) {
                $expiresHours = config('keystoneguru.live_sessions.expires_hours');

                $liveSession->expires_at = now()->addHours($expiresHours);
                $liveSession->save();

                if (Auth::check()) {
                    /** @var User $user */
                    $user = Auth::user();
                    broadcast(new StopEvent($liveSession, $user));
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
