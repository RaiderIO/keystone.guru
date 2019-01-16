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
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class FindOutdatedThumbnails
{
    use ChecksForDuplicateJobs;

    function __invoke()
    {
        Log::channel('scheduler')->debug('>> Finding thumbnails');
        /** @var Collection $routes */
        // Published routes get priority! This is only really relevant initially while processing the thumbnail queue
        $routes = DungeonRoute::orderBy('published', 'desc')->get();
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
                $thumbnailUpdatedAt->addMonth(1)->isPast()
                ||
                // Thumbnail does not exist in the folder it should
                !ProcessRouteFloorThumbnail::thumbnailsExistsForRoute($dungeonRoute)
                ) {

                if (!$this->isJobQueuedForModel(\App\Jobs\ProcessRouteFloorThumbnail::class, $dungeonRoute)) {
                    Log::channel('scheduler')->debug(sprintf('Queueing job for route %s (%s floors)', $dungeonRoute->public_key, $dungeonRoute->dungeon->floors->count()));

                    $dungeonRoute->queueRefreshThumbnails();
                    $processed++;
                }
            }
        }

        Log::channel('scheduler')->debug(sprintf('Scheduled processing for %s routes', $processed));
        Log::channel('scheduler')->debug('OK Finding thumbnails');
    }
}