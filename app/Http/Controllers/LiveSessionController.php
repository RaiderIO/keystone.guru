<?php

namespace App\Http\Controllers;

use App\Events\LiveSession\InviteEvent;
use App\Logic\MapContext\MapContextLiveSession;
use App\Models\DungeonRoute;
use App\Models\Floor;
use App\Models\LiveSession;
use App\Models\Team;
use App\Service\EchoServerHttpApiServiceInterface;
use App\User;
use Auth;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Teapot\StatusCode;

class LiveSessionController extends Controller
{
    /**
     * @param Request $request
     * @param DungeonRoute $dungeonroute
     * @param EchoServerHttpApiServiceInterface $echoServerHttpApiService
     * @return RedirectResponse
     * @throws AuthorizationException
     */
    public function create(Request $request, DungeonRoute $dungeonroute, EchoServerHttpApiServiceInterface $echoServerHttpApiService)
    {
        $this->authorize('view', $dungeonroute);

        $liveSession = LiveSession::create([
            'dungeon_route_id' => $dungeonroute->id,
            'user_id'          => Auth::id(),
            'public_key'       => LiveSession::generateRandomPublicKey(),
        ]);

        // If the team is set for this route, invite all team members that are currently viewing this route to join
        $user = Auth::user();
        if ($dungeonroute->team instanceof Team && $dungeonroute->team->isUserMember($user)) {
            try {
                // Propagate changes to the channel that the user came from
                $channelName = sprintf('presence-%s-route-edit.%s', config('app.type'), $dungeonroute->public_key);

                $invitees = collect();
                // Check if the user is in this channel..
                foreach ($echoServerHttpApiService->getChannelUsers($channelName) as $channelUser) {
                    $publicKey = $channelUser['public_key'] ?? $channelUser['user_info']['public_key'];
                    /** @var array $channelUser */
                    // Ignore the current user!
                    if (!isset($publicKey)) {
                        logger()->notice('Echo user public_key not set', $channelUser);
                    } else if ($publicKey !== $user->public_key &&
                        $dungeonroute->team->isUserMember(new User($channelUser))) {
                        $invitees->push($publicKey);
                    }
                }

                if ($invitees->isNotEmpty()) {
                    // Broadcast that channel that a team member has started a live session and that we're invited!
                    broadcast(new InviteEvent($liveSession, $user, $invitees));
                }
            } catch (Exception $exception) {
                report($exception);

                Log::error('Echo server is probably not running!');
            }
        }

        return redirect()->route('dungeonroute.livesession.view', ['dungeonroute' => $dungeonroute, 'livesession' => $liveSession]);
    }

    /**
     * @param Request $request
     * @param DungeonRoute $dungeonroute
     * @param LiveSession $livesession
     * @return Application|Factory|View|RedirectResponse
     * @throws AuthorizationException
     */
    public function view(Request $request, DungeonRoute $dungeonroute, LiveSession $livesession)
    {
        $defaultFloor = $dungeonroute->dungeon->floors()->where('default', true)->first();
        return $this->viewfloor($request, $dungeonroute, $livesession, optional($defaultFloor)->index ?? '1');
    }

    /**
     * @param Request $request
     * @param DungeonRoute $dungeonroute
     * @param LiveSession $livesession
     * @param string $floorIndex
     * @return Application|Factory|View|RedirectResponse
     * @throws AuthorizationException
     */
    public function viewfloor(Request $request, DungeonRoute $dungeonroute, LiveSession $livesession, string $floorIndex)
    {
        $this->authorize('view', $dungeonroute);
        try {
            $this->authorize('view', $livesession);
        } catch (AuthorizationException $ex) {
            abort(StatusCode::GONE);
        }

        if (!is_numeric($floorIndex)) {
            $floorIndex = '1';
        }

        /** @var Floor $floor */
        $floor = Floor::where('dungeon_id', $dungeonroute->dungeon_id)->where('index', $floorIndex)->first();

        if ($floor === null) {
            return redirect()->route('dungeonroute.livesession.view', [
                'dungeonroute' => $dungeonroute,
                'livesession'  => $livesession,
            ]);
        } else {
            return view('dungeonroute.livesession.view', [
                'dungeonroute' => $dungeonroute,
                'livesession'  => $livesession,
                'floor'        => $floor,
                'mapContext'   => (new MapContextLiveSession($livesession, $floor))->getProperties(),
            ]);
        }
    }
}
