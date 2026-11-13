<?php

namespace App\Http\Controllers;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\LiveSession\LiveSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LiveSessionLegacyController extends Controller
{
    public function view(
        Request      $request,
        DungeonRoute $dungeonRoute,
        LiveSession  $liveSession,
    ): RedirectResponse {
        return redirect()->route('dungeonroute.livesession.view', [
            'dungeon'      => $dungeonRoute->dungeon,
            'dungeonroute' => $dungeonRoute,
            'title'        => $dungeonRoute->getTitleSlug(),
            'liveSession'  => $liveSession,
        ]);
    }

    public function viewFloor(
        Request      $request,
        DungeonRoute $dungeonRoute,
        LiveSession  $liveSession,
        string       $floorIndex,
    ): RedirectResponse {
        return redirect()->route('dungeonroute.livesession.view', [
            'dungeon'      => $dungeonRoute->dungeon,
            'dungeonroute' => $dungeonRoute,
            'title'        => $dungeonRoute->getTitleSlug(),
            'liveSession'  => $liveSession,
            'floorIndex'   => $floorIndex,
        ]);
    }
}
