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
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Carbon;

class FindOutdatedThumbnails
{

    /**
     * @var Schedule
     */
    private $schedule;

    /**
     * FindOutdatedThumbnails constructor.
     * @param Schedule $schedule
     */
    public function __construct(Schedule $schedule)
    {
        $this->schedule = $schedule;
    }

    function __invoke()
    {
        foreach (DungeonRoute::all() as $dungeonRoute) {
            $updatedAt = Carbon::createFromTimeString($dungeonRoute->updated_at);
            $thumbnailUpdatedAt = Carbon::createFromTimeString($dungeonRoute->thumbnail_updated_at);
            // If the route has been updated in the past 30 minutes...
            if ($updatedAt->addMinute(30)->isPast() &&
                // Updated at is greater than the thumbnail updated at (don't keep updating thumbnails..
                $updatedAt->greaterThan($thumbnailUpdatedAt)) {
                // Set it for processing in a queue
                ProcessRouteThumbnail::dispatch($dungeonRoute);
            }
        }
    }
}