<?php

namespace App\Http\Controllers\AdminTools;

use App\Http\Controllers\Controller;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonRoute\DungeonRouteThumbnailVariant;
use App\Repositories\Interfaces\DungeonRoute\DungeonRouteThumbnailRepositoryInterface;
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

    public function thumbnailsregeneratesubmit(
        Request                                  $request,
        ThumbnailService                         $thumbnailService,
        DungeonRouteThumbnailRepositoryInterface $dungeonRouteThumbnailRepository,
    ): View {
        set_time_limit(3600);

        $dungeonId   = (int)$request->get('dungeon_id');
        $onlyMissing = (int)$request->get('only_missing');
        // Without this the queued job skips any route whose thumbnail_updated_at is already newer than
        // the route itself - which includes every thumbnail that rendered blank, since a blank render
        // still stamps thumbnail_updated_at (#4101).
        $force = (bool)$request->get('force');

        // ThumbnailService::queueThumbnailRefresh() reads dungeon and mappingVersion on every route
        $builder = DungeonRoute::with(['dungeon', 'mappingVersion'])
            ->when($dungeonId !== -1, static fn(Builder $builder) => $builder->where('dungeon_id', $dungeonId))
            ->orderByDesc('created_at');

        // Refreshing a route means refreshing the variants it actually has, hero included - resolved in a
        // single query up front rather than per route. Routes without a hero thumbnail deliberately do not
        // gain one here: only the discovery hero band displays it, and thumbnail:ensureheroes owns creating
        // it for exactly those routes.
        $heroThumbnailRouteIds = $dungeonRouteThumbnailRepository
            ->getDungeonRouteIdsWithVariant(
                DungeonRouteThumbnailVariant::Hero,
                $dungeonId === -1 ? null : $dungeonId,
            )
            ->flip();

        $successCount  = 0;
        $failureCount  = 0;
        $dungeonRoutes = $builder->get();
        foreach ($dungeonRoutes as $dungeonRoute) {
            $shouldRefresh = !$onlyMissing || !$thumbnailService->hasThumbnailsGenerated($dungeonRoute);

            if ($shouldRefresh) {
                if ($thumbnailService->queueThumbnailRefresh($dungeonRoute, $force)) {
                    $successCount++;
                } else {
                    $failureCount++;
                }

                // Note the two variants read "unforced" differently, by design in queueThumbnailRefresh():
                // the standard pass defers to the job's thumbnail_updated_at gate, while a non-standard pass
                // is gated on variant freshness and then always dispatches forced. So an unforced run here
                // still re-renders a hero thumbnail that is genuinely stale (a partial floor set, or a route
                // edited since the render) - which is what a refresh should do, and matches what the hourly
                // thumbnail:ensureheroes would do anyway. A fresh hero thumbnail is left alone.
                if ($heroThumbnailRouteIds->has($dungeonRoute->id)) {
                    $thumbnailService->queueThumbnailRefresh($dungeonRoute, $force, DungeonRouteThumbnailVariant::Hero);
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
