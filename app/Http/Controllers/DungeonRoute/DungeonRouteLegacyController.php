<?php

namespace App\Http\Controllers\DungeonRoute;

use App\Http\Controllers\Controller;
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
    public function viewOld(
        DungeonRouteBaseUrlFormRequest $request,
        ?DungeonRoute                  $dungeonRoute,
    ): RedirectResponse {
        if ($dungeonRoute === null) {
            abort(404);
        }

        return redirect()->route('dungeonroute.view', [
            'dungeon'      => $dungeonRoute->dungeon,
            'dungeonroute' => $dungeonRoute,
            'title'        => $dungeonRoute->getTitleSlug(),
        ] + $request->validated());
    }

    public function edit(
        DungeonRouteBaseUrlFormRequest $request,
        ?DungeonRoute                  $dungeonRoute,
    ): RedirectResponse {
        if ($dungeonRoute === null) {
            abort(404);
        }

        return redirect()->route('dungeonroute.edit', [
            'dungeon'      => $dungeonRoute->dungeon,
            'dungeonroute' => $dungeonRoute,
            'title'        => $dungeonRoute->getTitleSlug(),
        ] + $request->validated());
    }

    public function editFloor(
        DungeonRouteBaseUrlFormRequest $request,
        ?DungeonRoute                  $dungeonRoute,
        string                         $floorIndex,
    ): RedirectResponse {
        if ($dungeonRoute === null) {
            abort(404);
        }

        return redirect()->route('dungeonroute.edit.floor', [
            'dungeon'      => $dungeonRoute->dungeon,
            'dungeonroute' => $dungeonRoute,
            'title'        => $dungeonRoute->getTitleSlug(),
            'floorIndex'   => $floorIndex,
        ] + $request->validated());
    }

    public function embedOld(
        DungeonRouteEmbedUrlFormRequest $request,
        ?DungeonRoute                   $dungeonRoute,
        string                          $floorIndex = '1',
    ): RedirectResponse {
        if ($dungeonRoute === null) {
            abort(404);
        }

        return redirect()->route('dungeonroute.embed', array_merge([
            'dungeon'      => $dungeonRoute->dungeon,
            'dungeonroute' => $dungeonRoute,
            'title'        => $dungeonRoute->getTitleSlug(),
            'floorIndex'   => $floorIndex,
        ], $request->validated()));
    }

    public function viewFloorOld(
        DungeonRouteBaseUrlFormRequest $request,
        DungeonRoute                   $dungeonRoute,
        string                         $floorIndex,
    ): RedirectResponse {
        return redirect()->route('dungeonroute.view.floor', [
            'dungeon'      => $dungeonRoute->dungeon,
            'dungeonroute' => $dungeonRoute,
            'title'        => $dungeonRoute->getTitleSlug(),
            'floorIndex'   => $floorIndex,
        ] + $request->validated());
    }

    public function previewOld(
        DungeonRouteBaseUrlFormRequest $request,
        ?DungeonRoute                  $dungeonRoute,
        string                         $floorIndex,
    ): RedirectResponse {
        if ($dungeonRoute === null) {
            abort(404);
        }

        return redirect()->route('dungeonroute.preview', [
            'dungeon'      => $dungeonRoute->dungeon,
            'dungeonroute' => $dungeonRoute,
            'title'        => $dungeonRoute->getTitleSlug(),
            'floorIndex'   => $floorIndex,
        ] + $request->validated());
    }

    public function cloneOld(
        DungeonRouteBaseUrlFormRequest $request,
        ?DungeonRoute                  $dungeonRoute,
    ): RedirectResponse {
        if ($dungeonRoute === null) {
            abort(404);
        }

        return redirect()->route('dungeonroute.clone', [
            'dungeon'      => $dungeonRoute->dungeon,
            'dungeonroute' => $dungeonRoute,
            'title'        => $dungeonRoute->getTitleSlug(),
        ] + $request->validated());
    }

    public function claimOld(
        DungeonRouteBaseUrlFormRequest $request,
        ?DungeonRoute                  $dungeonRoute,
    ): RedirectResponse {
        if ($dungeonRoute === null) {
            abort(404);
        }

        return redirect()->route('dungeonroute.claim', [
            'dungeon'      => $dungeonRoute->dungeon,
            'dungeonroute' => $dungeonRoute,
            'title'        => $dungeonRoute->getTitleSlug(),
        ] + $request->validated());
    }
}
