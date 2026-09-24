<?php

namespace App\Jobs;

use App\Exceptions\ThumbnailRenderFailedException;
use App\Jobs\Logging\ProcessRouteFloorThumbnailLoggingInterface;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonRoute\DungeonRouteThumbnailVariant;
use App\Service\DungeonRoute\ThumbnailGenerationToggleServiceInterface;
use App\Service\DungeonRoute\ThumbnailServiceInterface;
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
     * The dungeon_routes.thumbnail_refresh_queued_at column default, meaning "no refresh is queued".
     */
    private const string THUMBNAIL_REFRESH_NEVER_QUEUED_AT = '1970-01-01 00:00:00';

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
     * @throws ThumbnailRenderFailedException
     */
    public function handle(): void
    {
        $result = null;

        // Cannot serialize these objects - so we have to create them here
        $thumbnailService = app()->make(ThumbnailServiceInterface::class);
        $log              = app()->make(ProcessRouteFloorThumbnailLoggingInterface::class);

        // Jobs queued before generation was paused would otherwise still render. Returning rather than
        // release()ing: a release consumes an attempt against $tries, so a pause outlasting the backoff
        // schedule would fail every queued job.
        if (app()->make(ThumbnailGenerationToggleServiceInterface::class)->isPaused()) {
            $log->handleThumbnailGenerationPaused();

            $this->resetThumbnailRefreshQueuedAt();

            return;
        }

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
                $result = $thumbnailService->createThumbnail(
                    $this->dungeonRoute,
                    $this->floorIndex,
                    $this->attempts(),
                    $this->variant,
                    $this->attempts() >= $this->tries,
                );

                if (!$result) {
                    $log->handleCreateThumbnailError();

                    // Throwing lets the queue worker's own retry mechanism re-schedule this job using
                    // $tries/backoff() below, instead of us re-dispatching a copy of ourselves. Not
                    // immediately, though: without a delay all $tries attempts would burn within ~35
                    // seconds, so a render that failed because the environment was momentarily slow (or
                    // busy with the other floors of this same route) would be retried while it is still
                    // just as slow. See #3920. Once $tries is exhausted the worker calls failed() below
                    // instead of retrying again.
                    throw new ThumbnailRenderFailedException(sprintf(
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
     * Rewinds the route's queue marker to the column's "never queued" default, so that discarding this job
     * does not make the route ineligible for a refresh for refresh_requeue_hours (72h) afterwards - which is
     * how DungeonRouteRepository::getDungeonRoutesWithExpiredThumbnails() reads a marker newer than
     * thumbnail_updated_at. Written through the query builder so the route's own updated_at is untouched.
     * Only the standard variant sets the marker; the others are gated on variant freshness instead.
     */
    private function resetThumbnailRefreshQueuedAt(): void
    {
        if ($this->variant !== DungeonRouteThumbnailVariant::Standard) {
            return;
        }

        DungeonRoute::query()
            ->whereKey($this->dungeonRoute->id)
            ->toBase()
            ->update(['thumbnail_refresh_queued_at' => self::THUMBNAIL_REFRESH_NEVER_QUEUED_AT]);
    }

    /**
     * Called by the queue worker once $tries is exhausted.
     */
    public function failed(?Throwable $exception): void
    {
        app()->make(ProcessRouteFloorThumbnailLoggingInterface::class)->handleMaxAttemptsReached();
    }
}
