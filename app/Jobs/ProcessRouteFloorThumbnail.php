<?php

namespace App\Jobs;

use App\Jobs\Logging\ProcessRouteFloorThumbnailLoggingInterface;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonRoute\DungeonRouteThumbnailVariant;
use App\Service\DungeonRoute\ThumbnailServiceInterface;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class ProcessRouteFloorThumbnail implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * How many times the queue worker will run this job before giving up and calling failed().
     * Set from config rather than a fixed property so keystoneguru.thumbnail.max_attempts stays the
     * single place that number lives. This overrides the `tries` the thumbnail Horizon supervisors
     * are configured with (1) - a job-level $tries always takes precedence over the worker's.
     */
    public int $tries;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected DungeonRoute                 $dungeonRoute,
        protected int                          $floorIndex,
        protected bool                         $force = false,
        protected DungeonRouteThumbnailVariant $variant = DungeonRouteThumbnailVariant::Standard,
    ) {
        $this->queue = sprintf('%s-thumbnail', config('app.type'));
        $this->tries = (int)config('keystoneguru.thumbnail.max_attempts');
    }

    /**
     * Staggered delay in seconds the queue worker waits before each retry.
     *
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [10, 60, 300]; // Wait 10s, then 60s, then 300s
    }

    /**
     * @throws Exception
     */
    public function handle(): void
    {
        $result = null;

        // Cannot serialize these objects - so we have to create them here
        $thumbnailService = app()->make(ThumbnailServiceInterface::class);
        $log              = app()->make(ProcessRouteFloorThumbnailLoggingInterface::class);

        try {
            $log->handleStart(
                $this->dungeonRoute->public_key,
                $this->dungeonRoute->id,
                $this->dungeonRoute->mapping_version_id,
                $this->floorIndex,
                $this->attempts(),
            );

            // Give some additional space since we're refreshing ALL floors - the first floor may get processed,
            // but the floors after that will otherwise think "oh the thumbnail is up-to-date" and not refresh.
            if ($this->dungeonRoute->thumbnail_updated_at->isBefore($this->dungeonRoute->updated_at->addHour()) || $this->force) {
                $result = $thumbnailService->createThumbnail($this->dungeonRoute, $this->floorIndex, $this->attempts(), $this->variant);

                if (!$result) {
                    $log->handleCreateThumbnailError();

                    // Throwing lets the queue worker's own retry mechanism re-schedule this job using
                    // $tries/backoff() below, instead of us re-dispatching a copy of ourselves. Not
                    // immediately, though: without a delay all $tries attempts would burn within ~35
                    // seconds, so a render that failed because the environment was momentarily slow (or
                    // busy with the other floors of this same route) would be retried while it is still
                    // just as slow. See #3920. Once $tries is exhausted the worker calls failed() below
                    // instead of retrying again.
                    throw new Exception(sprintf(
                        'Failed to create thumbnail for dungeon route %d floor %d on attempt %d',
                        $this->dungeonRoute->id,
                        $this->floorIndex,
                        $this->attempts(),
                    ));
                }
            } else {
                $log->handleThumbnailAlreadyUpToDate();
            }
        } finally {
            $log->handleEnd($result !== null);
        }
    }

    /**
     * Called by the queue worker once $tries is exhausted.
     */
    public function failed(?Throwable $exception): void
    {
        app()->make(ProcessRouteFloorThumbnailLoggingInterface::class)->handleMaxAttemptsReached();
    }
}
