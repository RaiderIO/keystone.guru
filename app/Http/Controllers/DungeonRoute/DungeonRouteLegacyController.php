<?php

namespace App\Http\Controllers\DungeonRoute;

use App\Http\Controllers\Controller;
use App\Http\Requests\DungeonRoute\DungeonRouteBaseUrlFormRequest;
use App\Http\Requests\DungeonRoute\DungeonRouteEmbedUrlFormRequest;
use App\Models\DungeonRoute\DungeonRoute;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Teapot\StatusCode;

/**
 * Class DungeonRouteLegacyController - contains all deprecated endpoints for dungeonroutes to keep the OG file clean
 *
 * @author Wouter
 *
 * @since 06/06/2022
 */
class DungeonRouteLegacyController extends Controller
{
    /**
     * @throws AuthorizationException
     */
    public function viewOld(
        DungeonRouteBaseUrlFormRequest $request,
        ?DungeonRoute                  $dungeonRoute,
    ): RedirectResponse {
        if ($dungeonRoute === null) {
            abort(404);
        }

        Gate::authorize('view', $dungeonRoute);

        return redirect()->route('dungeonroute.view', [
            'dungeon'      => $dungeonRoute->dungeon,
            'dungeonroute' => $dungeonRoute,
            'title'        => $dungeonRoute->getTitleSlug(),
        ] + $request->validated());
    }

    /**
     * @throws AuthorizationException
     */
    public function edit(
        DungeonRouteBaseUrlFormRequest $request,
        ?DungeonRoute                  $dungeonRoute,
    ): RedirectResponse {
        if ($dungeonRoute === null) {
            abort(404);
        }

        Gate::authorize('edit', $dungeonRoute);

        return redirect()->route('dungeonroute.edit', [
            'dungeon'      => $dungeonRoute->dungeon,
            'dungeonroute' => $dungeonRoute,
            'title'        => $dungeonRoute->getTitleSlug(),
        ] + $request->validated());
    }

    /**
     * @throws AuthorizationException
     */
    public function editFloor(
        DungeonRouteBaseUrlFormRequest $request,
        ?DungeonRoute                  $dungeonRoute,
        string                         $floorIndex,
    ): RedirectResponse {
        if ($dungeonRoute === null) {
            abort(404);
        }

        Gate::authorize('edit', $dungeonRoute);

        return redirect()->route('dungeonroute.edit.floor', [
            'dungeon'      => $dungeonRoute->dungeon,
            'dungeonroute' => $dungeonRoute,
            'title'        => $dungeonRoute->getTitleSlug(),
            'floorIndex'   => $floorIndex,
        ] + $request->validated());
    }

    /**
     * @throws AuthorizationException
     */
    public function embedOld(
        DungeonRouteEmbedUrlFormRequest $request,
        ?DungeonRoute                   $dungeonRoute,
        string                          $floorIndex = '1',
    ): RedirectResponse {
        if ($dungeonRoute === null) {
            abort(404);
        }

        Gate::authorize('embed', $dungeonRoute);

        return redirect()->route('dungeonroute.embed', array_merge([
            'dungeon'      => $dungeonRoute->dungeon,
            'dungeonroute' => $dungeonRoute,
            'title'        => $dungeonRoute->getTitleSlug(),
            'floorIndex'   => $floorIndex,
        ], $request->validated()));
    }

    /**
     * @throws AuthorizationException
     */
    public function viewFloorOld(
        DungeonRouteBaseUrlFormRequest $request,
        DungeonRoute                   $dungeonRoute,
        string                         $floorIndex,
    ): RedirectResponse {
        Gate::authorize('view', $dungeonRoute);

        return redirect()->route('dungeonroute.view.floor', [
            'dungeon'      => $dungeonRoute->dungeon,
            'dungeonroute' => $dungeonRoute,
            'title'        => $dungeonRoute->getTitleSlug(),
            'floorIndex'   => $floorIndex,
        ] + $request->validated());
    }

    /**
     * @throws AuthorizationException
     */
    public function previewOld(
        DungeonRouteBaseUrlFormRequest $request,
        ?DungeonRoute                  $dungeonRoute,
        string                         $floorIndex,
    ): RedirectResponse {
        if ($dungeonRoute === null) {
            abort(404);
        }

        Gate::authorize('preview', [
            $dungeonRoute,
            $request->get('secret', '') ?? '',
        ]);

        return redirect()->route('dungeonroute.preview', [
            'dungeon'      => $dungeonRoute->dungeon,
            'dungeonroute' => $dungeonRoute,
            'title'        => $dungeonRoute->getTitleSlug(),
            'floorIndex'   => $floorIndex,
        ] + $request->validated());
    }

    /**
     * @throws AuthorizationException
     */
    public function cloneOld(
        DungeonRouteBaseUrlFormRequest $request,
        ?DungeonRoute                  $dungeonRoute,
    ): RedirectResponse {
        if ($dungeonRoute === null) {
            abort(404);
        }

        Gate::authorize('clone', $dungeonRoute);

        return redirect()->route('dungeonroute.clone', [
            'dungeon'      => $dungeonRoute->dungeon,
            'dungeonroute' => $dungeonRoute,
            'title'        => $dungeonRoute->getTitleSlug(),
        ] + $request->validated(), StatusCode::TEMPORARY_REDIRECT);
    }

    /**
     * @throws AuthorizationException
     */
    public function claimOld(
        DungeonRouteBaseUrlFormRequest $request,
        ?DungeonRoute                  $dungeonRoute,
    ): RedirectResponse {
        if ($dungeonRoute === null) {
            abort(404);
        }

        Gate::authorize('view', $dungeonRoute);

        return redirect()->route('dungeonroute.claim', [
            'dungeon'      => $dungeonRoute->dungeon,
            'dungeonroute' => $dungeonRoute,
            'title'        => $dungeonRoute->getTitleSlug(),
        ] + $request->validated(), StatusCode::TEMPORARY_REDIRECT);
    }
}
