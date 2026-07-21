<?php

namespace App\Http\Controllers\AdminTools;

use App\Http\Controllers\Controller;
use App\Models\DungeonRoute\DungeonRoute;
use App\Service\DungeonRoute\ThumbnailService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Session;

class AdminToolsThumbnailsController extends Controller
{
    public function thumbnailsregenerate(): View
    {
        return view('admin.tools.thumbnails.regenerate');
    }

    public function thumbnailsregeneratesubmit(Request $request, ThumbnailService $thumbnailService): View
    {
        set_time_limit(3600);

        $dungeonId   = (int)$request->get('dungeon_id');
        $onlyMissing = (int)$request->get('only_missing');

        // ThumbnailService::queueThumbnailRefresh() reads dungeon and mappingVersion on every route
        $builder = DungeonRoute::with(['dungeon', 'mappingVersion'])
            ->when($dungeonId !== -1, static fn(Builder $builder) => $builder->where('dungeon_id', $dungeonId))
            ->orderByDesc('created_at');

        $successCount  = 0;
        $failureCount  = 0;
        $dungeonRoutes = $builder->get();
        foreach ($dungeonRoutes as $dungeonRoute) {
            $shouldRefresh = !$onlyMissing || !$thumbnailService->hasThumbnailsGenerated($dungeonRoute);

            if ($shouldRefresh) {
                if ($thumbnailService->queueThumbnailRefresh($dungeonRoute)) {
                    $successCount++;
                } else {
                    $failureCount++;
                }
            }
        }

        Session::flash('status', __('controller.admintools.flash.thumbnail_regenerate_result', [
            'success' => $successCount,
            'total'   => $successCount + $failureCount,
            'failed'  => $failureCount,
        ]));

        return view('admin.tools.thumbnails.regenerate');
    }
}
