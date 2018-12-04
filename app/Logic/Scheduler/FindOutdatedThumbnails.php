<?php
/**
 * Created by PhpStorm.
 * User: Wouter
 * Date: 03/12/2018
 * Time: 23:57
 */

namespace App\Logic\Scheduler;

use App\Jobs\ProcessRouteThumbnail;
use App\Models\DungeonRoute;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class FindOutdatedThumbnails
{
    function __invoke()
    {
        Log::channel('scheduler')->debug(sprintf('Checking %s routes for thumbnails', DungeonRoute::all()->count()));

        $processed = 0;
        foreach (DungeonRoute::all() as $dungeonRoute) {
            $updatedAt = Carbon::createFromTimeString($dungeonRoute->updated_at);
            $thumbnailUpdatedAt = Carbon::createFromTimeString($dungeonRoute->thumbnail_updated_at);

            // If the route has been updated in the past 30 minutes...
            if ($updatedAt->addMinute(30)->isPast() &&
                // Updated at is greater than the thumbnail updated at (don't keep updating thumbnails..
                $updatedAt->greaterThan($thumbnailUpdatedAt)) {
                // Set it for processing in a queue
                ProcessRouteThumbnail::dispatch($dungeonRoute);

                $processed++;
                break;
            }
        }

        Log::channel('scheduler')->debug(sprintf('Scheduled processing for %s routes', $processed));
    }
}