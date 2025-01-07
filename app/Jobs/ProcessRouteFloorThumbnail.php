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

    protected ThumbnailServiceInterface $thumbnailService;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected DungeonRoute                              $dungeonRoute,
        protected int                                       $floorIndex,
        protected int                                       $attempts = 0,
        private ?ProcessRouteFloorThumbnailLoggingInterface $log = null
    ) {
        // Not passed as a constructor parameter since it's not serializable
        $this->thumbnailService = app()->make(ThumbnailServiceInterface::class);
        $this->queue            = sprintf('%s-%s-thumbnail', config('app.type'), config('app.env'));
        $this->log              = $log ?? app()->make(ProcessRouteFloorThumbnailLoggingInterface::class);
    }

    /**
     * @throws Exception
     */
    public function handle(): void
    {
        $result = false;

        try {
            $this->log->handleStart(
                $this->dungeonRoute->public_key,
                $this->dungeonRoute->id,
                $this->dungeonRoute->mapping_version_id,
                $this->floorIndex,
                $this->attempts
            );

            if ((int)config('keystoneguru.thumbnail.max_attempts') > $this->attempts) {
                // Give some additional space since we're refreshing ALL floors - the first floor may get processed,
                // but the floors after that will otherwise think "oh the thumbnail is up-to-date" and not refresh.
                if ($this->dungeonRoute->thumbnail_updated_at->isBefore($this->dungeonRoute->updated_at->addHour())) {
                    $result = $this->thumbnailService->createThumbnail($this->dungeonRoute, $this->floorIndex, $this->attempts);

                    if (!$result) {
                        $this->log->handleCreateThumbnailError();

                        // If there were errors, try again
                        ProcessRouteFloorThumbnail::dispatch($this->dungeonRoute, $this->floorIndex, ++$this->attempts);
                    }
                } else {
                    $this->log->handleThumbnailAlreadyUpToDate();
                }
            } else {
                $this->log->handleMaxAttemptsReached();
            }
        } finally {
            $this->log->handleEnd($result);
        }
    }
}
