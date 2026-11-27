<?php

namespace App\Http\Controllers\Ajax;

use App\Events\LiveSession\StopEvent;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\ScopesToDungeonRoute;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\LiveSession\LiveSession;
use App\Models\User;
use App\Service\LiveSession\LiveSessionStateServiceInterface;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Broadcasting\BroadcastException;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Teapot\StatusCode\Http;

/**
 * @phpstan-import-type LiveSessionState from LiveSessionStateServiceInterface
 */
class AjaxLiveSessionController extends Controller
{
    use ScopesToDungeonRoute;

    /**
     * The current live state of the session, in the same shape the map context and the state broadcasts carry.
     *
     * @return LiveSessionState
     *
     * @throws AuthorizationException
     */
    public function state(
        LiveSessionStateServiceInterface $liveSessionStateService,
        DungeonRoute                     $dungeonRoute,
        LiveSession                      $liveSession,
    ): array {
        $this->abortUnlessBelongsToDungeonRoute($dungeonRoute, $liveSession);

        Gate::authorize('view', $dungeonRoute);
        Gate::authorize('view', $liveSession);

        return $liveSessionStateService->getState($liveSession);
    }

    /**
     * @return Response|ResponseFactory
     *
     * @throws AuthorizationException
     */
    public function delete(Request $request, DungeonRoute $dungeonRoute, LiveSession $liveSession)
    {
        // $dungeonRoute is unused below - kept only so the {dungeonRoute}/live/{liveSession} route's
        // implicit model binding still resolves both segments; dropping it entirely made the
        // CallableDispatcher's positional parameter resolution hand the raw dungeonRoute route
        // parameter to $liveSession instead. A live session may be started on any route its creator
        // can view, not just their own, so whether they may end it depends on the session's own
        // ownership, not the dungeon route's edit permissions.
        Gate::authorize('end', $liveSession);

        try {
            if ($liveSession->expires_at === null) {
                $expiresHours = config('keystoneguru.live_sessions.expires_hours');

                $liveSession->expires_at = now()->addHours($expiresHours);
                $liveSession->save();

                if (Auth::check()) {
                    /** @var User $user */
                    $user = Auth::user();

                    try {
                        broadcast(new StopEvent($liveSession, $user));
                    } catch (BroadcastException) {
                        // Ignore broadcast failures
                    }
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
