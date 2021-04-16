<?php
/**
 * Created by PhpStorm.
 * User: Wouter
 * Date: 03/12/2018
 * Time: 23:57
 */

namespace App\Logic\Scheduler;

use App\Models\DungeonRoute;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class RefreshOutdatedThumbnails
{
    function __invoke()
    {
        Log::channel('scheduler')->debug('>> Finding thumbnails');

        /** @var DungeonRoute[]|Collection $routes */
        $routes = DungeonRoute::whereNotNull('expires_at')
            // Only if it's not already queued!
            ->whereRaw('thumbnail_refresh_queued_at < thumbnail_updated_at')
            ->where(function (Builder $builder)
            {
                $builder->whereColumn('updated_at', '>', 'thumbnail_updated_at')
                    ->whereDate('updated_at', '<', now()->subMinutes(config('keystoneguru.thumbnail_refresh_min'))->toDateTimeString());
            })->orWhere('thumbnail_updated_at', '<', now()->subDays(config('keystoneguru.thumbnail_refresh_anyways_days')))
            // Published routes get priority! This is only really relevant initially while processing the thumbnail queue
            ->orderBy('published_state_id', 'desc')
            // Oldest first
            ->orderBy('id')
            ->get();

        Log::channel('scheduler')->debug(sprintf('Scheduling %s routes for thumbnail generation', $routes->count()));

        // All routes that come from the above will need their thumbnails regenerated, loop over them and queue the jobs at once
        foreach ($routes as $dungeonRoute) {
            $dungeonRoute->queueRefreshThumbnails();
        }

        Log::channel('scheduler')->debug('OK Finding thumbnails');
    }
}