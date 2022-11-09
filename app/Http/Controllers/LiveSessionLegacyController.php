<?php

namespace App\Http\Controllers;

use App\Models\DungeonRoute;
use App\Models\LiveSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class LiveSessionLegacyController extends Controller
{
    /**
     * @param Request $request
     * @param DungeonRoute $dungeonroute
     * @param LiveSession $livesession
     * @return RedirectResponse
     */
    public function view(Request $request, DungeonRoute $dungeonroute, LiveSession $livesession)
    {
        return redirect()->route('dungeonroute.livesession.view', [
            'dungeon'      => $dungeonroute->dungeon,
            'dungeonroute' => $dungeonroute,
            'title'        => Str::slug($dungeonroute->title),
            'livesession'  => $livesession,
        ]);
    }

    /**
     * @param Request $request
     * @param DungeonRoute $dungeonroute
     * @param LiveSession $livesession
     * @param string $floorIndex
     * @return RedirectResponse
     */
    public function viewfloor(Request $request, DungeonRoute $dungeonroute, LiveSession $livesession, string $floorIndex)
    {
        return redirect()->route('dungeonroute.livesession.view', [
            'dungeon'      => $dungeonroute->dungeon,
            'dungeonroute' => $dungeonroute,
            'title'        => Str::slug($dungeonroute->title),
            'livesession'  => $livesession,
            'floorindex'   => $floorIndex,
        ]);
    }
}
