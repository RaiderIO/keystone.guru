<?php

namespace App\Http\Controllers;

use App\Events\LiveSession\InviteEvent;
use App\Models\Dungeon;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Floor\Floor;
use App\Models\LiveSession;
use App\Models\Team;
use App\Models\User;
use App\Service\MapContext\MapContextServiceInterface;
use App\Service\Reverb\ReverbHttpApiServiceInterface;
use Auth;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Teapot\StatusCode;

class LiveSessionController extends Controller
{
    /**
     * @throws AuthorizationException
     */
    public function create(
        Request                       $request,
        Dungeon                       $dungeon,
        DungeonRoute                  $dungeonroute,
        ?string                       $title,
        ReverbHttpApiServiceInterface $reverbHttpApiService,
    ): RedirectResponse {
        Gate::authorize('view', $dungeonroute);

        $liveSession = LiveSession::create([
            'dungeon_route_id' => $dungeonroute->id,
            'user_id'          => Auth::id(),
            'public_key'       => LiveSession::generateRandomPublicKey(),
        ]);

        // If the team is set for this route, invite all team members that are currently viewing this route to join
        /** @var User|null $user */
        $user = Auth::user();
        if ($dungeonroute->team instanceof Team && $dungeonroute->team->isUserMember($user)) {
            try {
                // Propagate changes to the channel that the user came from
                $channelName = sprintf('presence-%s-route-edit.%s', config('app.type'), $dungeonroute->public_key);

                $invitees = collect();
                // Check if the user is in this channel..
                foreach ($reverbHttpApiService->getChannelUsers($channelName) as $reverbChannelUser) {
                    /** @var array{id: string} $reverbChannelUser */
                    // Ignore the current user!
                    $channelUser = User::find($reverbChannelUser['id']);
                    if ($channelUser !== null &&
                        $channelUser->id !== $user->id &&
                        $dungeonroute->team->isUserMember($channelUser)) {
                        $invitees->push($channelUser->public_key);
                    }
                }

                if ($invitees->isNotEmpty()) {
                    // Broadcast that channel that a team member has started a live session and that we're invited!
                    broadcast(new InviteEvent($liveSession, $user, $invitees));
                }
            } catch (Exception $exception) {
                report($exception);

                Log::error('Reverb server is probably not running!');
            }
        }

        return redirect()->route('dungeonroute.livesession.view', [
            'dungeon'      => $dungeonroute->dungeon,
            'dungeonroute' => $dungeonroute,
            'title'        => $dungeonroute->getTitleSlug(),
            'liveSession'  => $liveSession,
        ]);
    }

    /**
     * @return Application|Factory|View|RedirectResponse
     *
     * @throws AuthorizationException
     */
    public function view(
        Request                    $request,
        MapContextServiceInterface $mapContextService,
        Dungeon                    $dungeon,
        DungeonRoute               $dungeonroute,
        ?string                    $title,
        LiveSession                $liveSession,
    ) {
        $defaultFloor = $dungeonroute->dungeon->floors()->where('default', true)->first();

        return $this->viewFloor(
            $request,
            $mapContextService,
            $dungeon,
            $dungeonroute,
            $title,
            $liveSession,
            $defaultFloor?->index ?? '1',
        );
    }

    /**
     * @return Application|Factory|View|RedirectResponse
     *
     * @throws AuthorizationException
     */
    public function viewFloor(
        Request                    $request,
        MapContextServiceInterface $mapContextService,
        Dungeon                    $dungeon,
        DungeonRoute               $dungeonroute,
        ?string                    $title,
        LiveSession                $liveSession,
        string                     $floorIndex,
    ) {
        Gate::authorize('view', $dungeonroute);

        try {
            Gate::authorize('view', $liveSession);
        } catch (AuthorizationException) {
            abort(StatusCode::GONE);
        }

        // In case someone edits the url to something funky
        if ($dungeonroute->id !== $liveSession->dungeon_route_id) {
            abort(404);
        }

        // It's broken - get rid of it
        if ($liveSession->dungeonRoute === null) {
            $liveSession->delete();
            abort(404);
        }

        if (!is_numeric($floorIndex)) {
            $floorIndex = '1';
        }

        /** @var Floor $floor */
        $floor = Floor::where('dungeon_id', $dungeonroute->dungeon_id)
            ->indexOrFacade($dungeonroute->mappingVersion, $floorIndex)
            ->first();

        if ($floor === null) {
            return redirect()->route('dungeonroute.livesession.view', [
                'dungeon'      => $dungeonroute->dungeon,
                'dungeonroute' => $dungeonroute,
                'title'        => $dungeonroute->getTitleSlug(),
                'liveSession'  => $liveSession,
            ]);
        } else {
            return view('dungeonroute.livesession.view', [
                'dungeon'      => $dungeonroute->dungeon,
                'dungeonroute' => $dungeonroute,
                'title'        => $dungeonroute->getTitleSlug(),
                'livesession'  => $liveSession,
                'floor'        => $floor,
                'mapContext'   => $mapContextService->createMapContextLiveSession($liveSession, User::getCurrentUserMapFacadeStyle()),
            ]);
        }
    }
}
