<?php

namespace App\Http\Controllers;

use App\Models\DungeonRoute\DungeonRoute;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Class DungeonRouteLegacyController - contains all deprecated endpoints for dungeonroutes to keep the OG file clean
 *
 * @author Wouter
 *
 * @since 06/06/2022
 */
class DungeonRouteLegacyController extends Controller
{
    public function viewold(Request $request, DungeonRoute $dungeonroute): RedirectResponse
    {
        return redirect()->route('dungeonroute.view', [
            'dungeon'      => $dungeonroute->dungeon,
            'dungeonroute' => $dungeonroute,
            'title'        => $dungeonroute->getTitleSlug(),
        ]);
    }

    public function edit(Request $request, DungeonRoute $dungeonroute): RedirectResponse
    {
        return redirect()->route('dungeonroute.edit', [
            'dungeon'      => $dungeonroute->dungeon,
            'dungeonroute' => $dungeonroute,
            'title'        => $dungeonroute->getTitleSlug(),
        ]);
    }

    public function editfloor(Request $request, DungeonRoute $dungeonroute, string $floorIndex): RedirectResponse
    {
        return redirect()->route('dungeonroute.edit.floor', [
            'dungeon'      => $dungeonroute->dungeon,
            'dungeonroute' => $dungeonroute,
            'title'        => $dungeonroute->getTitleSlug(),
            'floorIndex'   => $floorIndex,
        ]);
    }

    public function embedold(Request $request, DungeonRoute $dungeonroute, string $floorIndex = '1'): RedirectResponse
    {
        return redirect()->route('dungeonroute.embed', array_merge([
            'dungeon'      => $dungeonroute->dungeon,
            'dungeonroute' => $dungeonroute,
            'title'        => $dungeonroute->getTitleSlug(),
            'floorIndex'   => $floorIndex,
        ], $request->all()));
    }

    public function viewfloorold(Request $request, DungeonRoute $dungeonroute, string $floorIndex): RedirectResponse
    {
        return redirect()->route('dungeonroute.view.floor', [
            'dungeon'      => $dungeonroute->dungeon,
            'dungeonroute' => $dungeonroute,
            'title'        => $dungeonroute->getTitleSlug(),
            'floorIndex'   => $floorIndex,
        ]);
    }

    public function previewold(Request $request, DungeonRoute $dungeonroute, string $floorIndex): RedirectResponse
    {
        return redirect()->route('dungeonroute.preview', [
            'dungeon'      => $dungeonroute->dungeon,
            'dungeonroute' => $dungeonroute,
            'title'        => $dungeonroute->getTitleSlug(),
            'floorIndex'   => $floorIndex,
        ]);
    }

    public function cloneold(Request $request, DungeonRoute $dungeonroute): RedirectResponse
    {
        return redirect()->route('dungeonroute.clone', [
            'dungeon'      => $dungeonroute->dungeon,
            'dungeonroute' => $dungeonroute,
            'title'        => $dungeonroute->getTitleSlug(),
        ]);
    }

    public function claimold(Request $request, DungeonRoute $dungeonroute): RedirectResponse
    {
        return redirect()->route('dungeonroute.claim', [
            'dungeon'      => $dungeonroute->dungeon,
            'dungeonroute' => $dungeonroute,
            'title'        => $dungeonroute->getTitleSlug(),
        ]);
    }
}
