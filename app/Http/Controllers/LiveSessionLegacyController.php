<?php

namespace App\Http\Controllers;

use App\Events\LiveSession\InviteEvent;
use App\Logic\MapContext\MapContextLiveSession;
use App\Models\Dungeon;
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
use Illuminate\Support\Str;
use Psr\SimpleCache\InvalidArgumentException;
use Teapot\StatusCode;

class LiveSessionLegacyController extends Controller
{
    /**
     * @param Request $request
     * @param Dungeon $dungeon
     * @param DungeonRoute $dungeonroute
     * @param string|null $title
     * @param LiveSession $liveSession
     * @return RedirectResponse
     * @throws AuthorizationException
     */
    public function view(Request $request, Dungeon $dungeon, DungeonRoute $dungeonroute, ?string $title, LiveSession $liveSession)
    {
        return redirect()->route('dungeonroute.livesession.view', [
            'dungeon'      => $dungeonroute->dungeon,
            'dungeonroute' => $dungeonroute,
            'title'        => Str::slug($dungeonroute->title),
            'livesession'  => $liveSession,
        ]);
    }

    /**
     * @param Request $request
     * @param Dungeon $dungeon
     * @param DungeonRoute $dungeonroute
     * @param string|null $title
     * @param LiveSession $liveSession
     * @param string $floorIndex
     * @return RedirectResponse
     * @throws InvalidArgumentException
     */
    public function viewfloor(Request $request, Dungeon $dungeon, DungeonRoute $dungeonroute, ?string $title, LiveSession $liveSession, string $floorIndex)
    {
        return redirect()->route('dungeonroute.livesession.view', [
            'dungeon'      => $dungeonroute->dungeon,
            'dungeonroute' => $dungeonroute,
            'title'        => Str::slug($dungeonroute->title),
            'livesession'  => $liveSession,
            'floorindex'   => $floorIndex,
        ]);
    }
}
