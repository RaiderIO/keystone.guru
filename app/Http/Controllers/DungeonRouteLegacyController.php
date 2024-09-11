<?php

namespace App\Http\Controllers;

use App\Http\Requests\DungeonRoute\DungeonRouteBaseUrlFormRequest;
use App\Http\Requests\DungeonRoute\DungeonRouteEmbedUrlFormRequest;
use App\Models\DungeonRoute\DungeonRoute;
use Illuminate\Http\RedirectResponse;

/**
 * Class DungeonRouteLegacyController - contains all deprecated endpoints for dungeonroutes to keep the OG file clean
 *
 * @author Wouter
 *
 * @since 06/06/2022
 */
class DungeonRouteLegacyController extends Controller
{
    public function viewOld(DungeonRouteBaseUrlFormRequest $request, DungeonRoute $dungeonroute): RedirectResponse
    {
        return redirect()->route('dungeonroute.view', [
            'dungeon'      => $dungeonroute->dungeon,
            'dungeonroute' => $dungeonroute,
            'title'        => $dungeonroute->getTitleSlug(),
        ] + $request->validated());
    }

    public function edit(DungeonRouteBaseUrlFormRequest $request, DungeonRoute $dungeonroute): RedirectResponse
    {
        return redirect()->route('dungeonroute.edit', [
            'dungeon'      => $dungeonroute->dungeon,
            'dungeonroute' => $dungeonroute,
            'title'        => $dungeonroute->getTitleSlug(),
        ] + $request->validated());
    }

    public function editFloor(DungeonRouteBaseUrlFormRequest $request, DungeonRoute $dungeonroute, string $floorIndex): RedirectResponse
    {
        return redirect()->route('dungeonroute.edit.floor', [
            'dungeon'      => $dungeonroute->dungeon,
            'dungeonroute' => $dungeonroute,
            'title'        => $dungeonroute->getTitleSlug(),
            'floorIndex'   => $floorIndex,
        ] + $request->validated());
    }

    public function embedOld(DungeonRouteEmbedUrlFormRequest $request, DungeonRoute $dungeonroute, string $floorIndex = '1'): RedirectResponse
    {
        return redirect()->route('dungeonroute.embed', array_merge([
            'dungeon'      => $dungeonroute->dungeon,
            'dungeonroute' => $dungeonroute,
            'title'        => $dungeonroute->getTitleSlug(),
            'floorIndex'   => $floorIndex,
        ], $request->validated()));
    }

    public function viewFloorOld(DungeonRouteBaseUrlFormRequest $request, DungeonRoute $dungeonroute, string $floorIndex): RedirectResponse
    {
        return redirect()->route('dungeonroute.view.floor', [
            'dungeon'      => $dungeonroute->dungeon,
            'dungeonroute' => $dungeonroute,
            'title'        => $dungeonroute->getTitleSlug(),
            'floorIndex'   => $floorIndex,
        ] + $request->validated());
    }

    public function previewOld(DungeonRouteBaseUrlFormRequest $request, DungeonRoute $dungeonroute, string $floorIndex): RedirectResponse
    {
        return redirect()->route('dungeonroute.preview', [
            'dungeon'      => $dungeonroute->dungeon,
            'dungeonroute' => $dungeonroute,
            'title'        => $dungeonroute->getTitleSlug(),
            'floorIndex'   => $floorIndex,
        ] + $request->validated());
    }

    public function cloneOld(DungeonRouteBaseUrlFormRequest $request, DungeonRoute $dungeonroute): RedirectResponse
    {
        return redirect()->route('dungeonroute.clone', [
            'dungeon'      => $dungeonroute->dungeon,
            'dungeonroute' => $dungeonroute,
            'title'        => $dungeonroute->getTitleSlug(),
        ] + $request->validated());
    }

    public function claimOld(DungeonRouteBaseUrlFormRequest $request, DungeonRoute $dungeonroute): RedirectResponse
    {
        return redirect()->route('dungeonroute.claim', [
            'dungeon'      => $dungeonroute->dungeon,
            'dungeonroute' => $dungeonroute,
            'title'        => $dungeonroute->getTitleSlug(),
        ] + $request->validated());
    }
}
