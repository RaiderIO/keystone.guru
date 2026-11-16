<?php

namespace App\Http\Controllers;

use App\Jobs\DropCaches;
use App\Service\DungeonRoute\TestDungeonRouteGeneratorServiceInterface;
use App\Service\DungeonRoute\ThumbnailGenerationToggleServiceInterface;
use App\Service\ReadOnlyMode\ReadOnlyModeServiceInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Session;

class AdminToolsController extends Controller
{
    public function index(
        TestDungeonRouteGeneratorServiceInterface $testDungeonRouteGeneratorService,
        ThumbnailGenerationToggleServiceInterface $thumbnailGenerationToggleService,
    ): View {
        return view('admin.tools.list', [
            'testDungeonRouteGeneratorAvailable' => $testDungeonRouteGeneratorService->isAvailable(),
            'thumbnailGenerationPaused'          => $thumbnailGenerationToggleService->isPaused(),
        ]);
    }

    public function dropCache(Request $request): RedirectResponse
    {
        DropCaches::dispatch();

        Session::flash('status', __('controller.admintools.flash.caches_drop_queued'));

        return redirect()->route('admin.tools');
    }

    public function toggleReadOnlyMode(
        Request                      $request,
        ReadOnlyModeServiceInterface $readOnlyModeService,
    ): RedirectResponse {
        if ($readOnlyModeService->isReadOnly()) {
            $readOnlyModeService->setReadOnly(false);
            Session::flash('status', __('controller.admintools.flash.read_only_mode_disabled'));
        } else {
            $readOnlyModeService->setReadOnly(true);
            Session::flash('status', __('controller.admintools.flash.read_only_mode_enabled'));
        }

        return redirect()->route('admin.tools');
    }

    public function toggleThumbnailGeneration(
        Request                                   $request,
        ThumbnailGenerationToggleServiceInterface $thumbnailGenerationToggleService,
    ): RedirectResponse {
        if ($thumbnailGenerationToggleService->isPaused()) {
            $thumbnailGenerationToggleService->setPaused(false);
            Session::flash('status', __('controller.admintools.flash.thumbnail_generation_resumed'));
        } else {
            $thumbnailGenerationToggleService->setPaused(true);
            Session::flash('status', __('controller.admintools.flash.thumbnail_generation_paused'));
        }

        return redirect()->route('admin.tools');
    }
}
