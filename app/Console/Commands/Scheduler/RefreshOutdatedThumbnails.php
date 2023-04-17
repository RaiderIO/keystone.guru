<?php

namespace App\Console\Commands\Scheduler;

use App\Models\DungeonRoute;
use App\Service\DungeonRoute\ThumbnailServiceInterface;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class RefreshOutdatedThumbnails extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scheduler:refreshoutdatedthumbnails';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refreshes outdated thumbnails for dungeonroutes';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     * @throws Exception
     */
    public function handle(ThumbnailServiceInterface $thumbnailService)
    {
        Log::channel('scheduler')->debug('>> Finding thumbnails');

        /** @var DungeonRoute[]|Collection $routes */
        $routes = DungeonRoute::where('author_id', '>', '0')
            // Check if in queue, if so skip, unless the queue age is longer than keystoneguru.thumbnail.refresh_requeue_hours
            ->where(function (Builder $builder) {
                $builder->whereColumn('thumbnail_refresh_queued_at', '<', 'thumbnail_updated_at')
                    ->orWhere(function (Builder $builder) {
                        // If it is in the queue to be refreshed
                        $builder->whereColumn('thumbnail_refresh_queued_at', '>', 'thumbnail_updated_at')
                            ->whereDate('thumbnail_refresh_queued_at', '<', now()->subHours(config('keystoneguru.thumbnail.refresh_requeue_hours'))->toDateTimeString());
                    });
            })
            ->where(function (Builder $builder) {
                // Only if it's not already queued!
                $builder->where(function (Builder $builder) {
                    $builder->whereColumn('updated_at', '>', 'thumbnail_updated_at')
                        ->whereDate('updated_at', '<', now()->subMinutes(config('keystoneguru.thumbnail.refresh_min'))->toDateTimeString());
                })->orWhereDate('thumbnail_updated_at', '<', now()->subDays(config('keystoneguru.thumbnail.refresh_anyways_days')));
            })
            // Published routes get priority! This is only really relevant initially while processing the thumbnail queue
            ->orderBy('published_state_id', 'desc')
            // Oldest first
            ->orderBy('id')
            ->get();

        Log::channel('scheduler')->debug(sprintf('Scheduling %s routes for thumbnail generation', $routes->count()));

        // All routes that come from the above will need their thumbnails regenerated, loop over them and queue the jobs at once
        foreach ($routes as $dungeonRoute) {
            $thumbnailService->queueThumbnailRefresh($dungeonRoute);
        }

        Log::channel('scheduler')->debug('OK Finding thumbnails');

        return 0;
    }
}
