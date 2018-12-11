<?php
/**
 * Created by PhpStorm.
 * User: Wouter
 * Date: 03/12/2018
 * Time: 23:57
 */

namespace App\Logic\Scheduler;

use App\Jobs\ProcessRouteFloorThumbnail;
use App\Models\DungeonRoute;
use App\Models\Floor;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class FindOutdatedThumbnails
{
    use ChecksForDuplicateJobs;

    function __invoke()
    {
        /** @var Builder $routes */
        $routes = DungeonRoute::all();
        Log::channel('scheduler')->debug(sprintf('Checking %s routes for thumbnails', $routes->count()));

        $processed = 0;
        foreach ($routes as $dungeonRoute) {
            /** @var DungeonRoute $dungeonRoute */
            $updatedAt = Carbon::createFromTimeString($dungeonRoute->updated_at);
            $thumbnailUpdatedAt = Carbon::createFromTimeString($dungeonRoute->thumbnail_updated_at);

            if ((// Updated at is greater than the thumbnail updated at (don't keep updating thumbnails..)
                    $updatedAt->greaterThan($thumbnailUpdatedAt) &&
                    // If the route has been updated in the past x minutes...
                    $updatedAt->addMinute(config('keystoneguru.thumbnail_refresh_min'))->isPast())
                ||
                // Update every month regardless
                $updatedAt->addMonth(1)->isPast()) {

                if (!$this->isJobQueuedForModel('App\Jobs\ProcessRouteFloorThumbnail', $dungeonRoute)) {
                    Log::channel('scheduler')->debug(sprintf('Queueing job for route %s (%s floors)', $dungeonRoute->public_key, $dungeonRoute->dungeon->floors->count()));

                    foreach ($dungeonRoute->dungeon->floors as $floor) {
                        /** @var Floor $floor */
                        // Set it for processing in a queue
                        ProcessRouteFloorThumbnail::dispatch($dungeonRoute, $floor->index);
                    }

                    $processed++;
                }
            }
        }

        Log::channel('scheduler')->debug(sprintf('Scheduled processing for %s routes', $processed));
    }
}