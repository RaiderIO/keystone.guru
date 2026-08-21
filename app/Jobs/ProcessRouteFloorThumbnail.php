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

class ProcessRouteFloorThumbnail implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected DungeonRoute                 $dungeonRoute,
        protected int                          $floorIndex,
        protected bool                         $force = false,
        protected int                          $attempts = 0,
        protected DungeonRouteThumbnailVariant $variant = DungeonRouteThumbnailVariant::Standard,
    ) {
        $this->queue = sprintf('%s-thumbnail', config('app.type'));
    }

    /**
     * @throws Exception
     */
    public function handle(): void
    {
        $result = false;

        // Cannot serialize these objects - so we have to create them here
        $thumbnailService = app()->make(ThumbnailServiceInterface::class);
        $log              = app()->make(ProcessRouteFloorThumbnailLoggingInterface::class);

        try {
            $log->handleStart(
                $this->dungeonRoute->public_key,
                $this->dungeonRoute->id,
                $this->dungeonRoute->mapping_version_id,
                $this->floorIndex,
                $this->attempts,
            );

            if ((int)config('keystoneguru.thumbnail.max_attempts') > $this->attempts) {
                // Give some additional space since we're refreshing ALL floors - the first floor may get processed,
                // but the floors after that will otherwise think "oh the thumbnail is up-to-date" and not refresh.
                if ($this->dungeonRoute->thumbnail_updated_at->isBefore($this->dungeonRoute->updated_at->addHour()) || $this->force) {
                    $result = $thumbnailService->createThumbnail($this->dungeonRoute, $this->floorIndex, $this->attempts, $this->variant);

                    if (!$result) {
                        $log->handleCreateThumbnailError();

                        // If there were errors, try again - but not immediately. Without a delay all
                        // max_attempts tries burn within ~35 seconds, so a render that failed because the
                        // environment was momentarily slow (or busy with the other floors of this same
                        // route) is retried while it is still just as slow. See #3920.
                        $nextAttempt  = $this->attempts + 1;
                        $delaySeconds = $this->getBackoffSecondsForAttempt($nextAttempt);

                        ProcessRouteFloorThumbnail::dispatch($this->dungeonRoute, $this->floorIndex, $this->force, $nextAttempt, $this->variant)
                            ->delay(now()->addSeconds($delaySeconds));

                        $log->handleCreateThumbnailRetryScheduled($nextAttempt, $delaySeconds);
                    }
                } else {
                    $log->handleThumbnailAlreadyUpToDate();
                }
            } else {
                $log->handleMaxAttemptsReached();
            }
        } finally {
            $log->handleEnd($result !== null);
        }
    }

    /**
     * Staggered delay in seconds for each retry attempt.
     *
     * Note that this job does not lean on the queue worker's own retry mechanism - it re-dispatches
     * itself on failure (so it can carry its own $attempts counter past a `tries: 1` worker), which
     * means the framework never consults this method by itself. getBackoffSecondsForAttempt() reads
     * it when scheduling that re-dispatch, so this array stays the single place the delays live.
     *
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [10, 60, 300]; // Wait 10s, then 60s, then 300s
    }

    /**
     * The delay to apply before the given (1-based) retry attempt. Attempts beyond the configured
     * delays reuse the last one, so raising keystoneguru.thumbnail.max_attempts never falls back to
     * retrying immediately.
     */
    private function getBackoffSecondsForAttempt(int $attempt): int
    {
        $backoff = $this->backoff();

        return $backoff[$attempt - 1] ?? (int)end($backoff);
    }
}
