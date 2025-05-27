<?php

namespace App\Jobs;

use App\Jobs\Logging\ProcessRouteFloorThumbnailLoggingInterface;
use App\Models\DungeonRoute\DungeonRoute;
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
        protected DungeonRoute $dungeonRoute,
        protected int          $floorIndex,
        protected bool         $force = false,
        protected int          $attempts = 0,
    ) {
        $this->queue = sprintf('%s-%s-thumbnail', config('app.type'), config('app.env'));
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
                $this->attempts
            );

            if ((int)config('keystoneguru.thumbnail.max_attempts') > $this->attempts) {
                // Give some additional space since we're refreshing ALL floors - the first floor may get processed,
                // but the floors after that will otherwise think "oh the thumbnail is up-to-date" and not refresh.
                if ($this->dungeonRoute->thumbnail_updated_at->isBefore($this->dungeonRoute->updated_at->addHour()) || $this->force) {
                    $result = $thumbnailService->createThumbnail($this->dungeonRoute, $this->floorIndex, $this->attempts);

                    if (!$result) {
                        $log->handleCreateThumbnailError();

                        // If there were errors, try again
                        ProcessRouteFloorThumbnail::dispatch($this->dungeonRoute, $this->floorIndex, $this->force, ++$this->attempts);
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
}
