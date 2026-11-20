<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\ScopesToDungeonRoute;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\LiveSession\LiveSession;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class LiveSessionLegacyController extends Controller
{
    use ScopesToDungeonRoute;

    /**
     * @throws AuthorizationException
     */
    public function view(
        Request      $request,
        DungeonRoute $dungeonRoute,
        LiveSession  $liveSession,
    ): RedirectResponse {
        Gate::authorize('view', $dungeonRoute);
        $this->abortUnlessBelongsToDungeonRoute($dungeonRoute, $liveSession);

        return redirect()->route('dungeonroute.livesession.view', [
            'dungeon'      => $dungeonRoute->dungeon,
            'dungeonroute' => $dungeonRoute,
            'title'        => $dungeonRoute->getTitleSlug(),
            'liveSession'  => $liveSession,
        ]);
    }

    /**
     * @throws AuthorizationException
     */
    public function viewFloor(
        Request      $request,
        DungeonRoute $dungeonRoute,
        LiveSession  $liveSession,
        string       $floorIndex,
    ): RedirectResponse {
        Gate::authorize('view', $dungeonRoute);
        $this->abortUnlessBelongsToDungeonRoute($dungeonRoute, $liveSession);

        return redirect()->route('dungeonroute.livesession.view', [
            'dungeon'      => $dungeonRoute->dungeon,
            'dungeonroute' => $dungeonRoute,
            'title'        => $dungeonRoute->getTitleSlug(),
            'liveSession'  => $liveSession,
            'floorIndex'   => $floorIndex,
        ]);
    }
}
