<?php

namespace App\Http\Controllers;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\LiveSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LiveSessionLegacyController extends Controller
{
    public function view(Request $request, DungeonRoute $dungeonroute, LiveSession $livesession): RedirectResponse
    {
        return redirect()->route('dungeonroute.livesession.view', [
            'dungeon' => $dungeonroute->dungeon,
            'dungeonroute' => $dungeonroute,
            'title' => $dungeonroute->getTitleSlug(),
            'livesession' => $livesession,
        ]);
    }

    public function viewfloor(Request $request, DungeonRoute $dungeonroute, LiveSession $livesession, string $floorIndex): RedirectResponse
    {
        return redirect()->route('dungeonroute.livesession.view', [
            'dungeon' => $dungeonroute->dungeon,
            'dungeonroute' => $dungeonroute,
            'title' => $dungeonroute->getTitleSlug(),
            'livesession' => $livesession,
            'floorindex' => $floorIndex,
        ]);
    }
}
