<?php

namespace App\Http\Controllers;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\LiveSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LiveSessionLegacyController extends Controller
{
    /**
     * @return RedirectResponse
     */
    public function view(Request $request, DungeonRoute $dungeonroute, LiveSession $livesession)
    {
        return redirect()->route('dungeonroute.livesession.view', [
            'dungeon'      => $dungeonroute->dungeon,
            'dungeonroute' => $dungeonroute,
            'title'        => $dungeonroute->getTitleSlug(),
            'livesession'  => $livesession,
        ]);
    }

    /**
     * @return RedirectResponse
     */
    public function viewfloor(Request $request, DungeonRoute $dungeonroute, LiveSession $livesession, string $floorIndex)
    {
        return redirect()->route('dungeonroute.livesession.view', [
            'dungeon'      => $dungeonroute->dungeon,
            'dungeonroute' => $dungeonroute,
            'title'        => $dungeonroute->getTitleSlug(),
            'livesession'  => $livesession,
            'floorindex'   => $floorIndex,
        ]);
    }
}
