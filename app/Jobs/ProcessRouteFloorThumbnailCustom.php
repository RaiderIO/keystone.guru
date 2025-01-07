<?php

namespace App\Jobs;

use App\Jobs\Logging\ProcessRouteFloorThumbnailCustomLoggingInterface;
use App\Jobs\Logging\ProcessRouteFloorThumbnailLoggingInterface;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonRoute\DungeonRouteThumbnailJob;
use App\Service\DungeonRoute\ThumbnailServiceInterface;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessRouteFloorThumbnailCustom
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
        private readonly DungeonRouteThumbnailJob                 $dungeonRouteThumbnailJob,
        protected DungeonRoute                                    $dungeonRoute,
        protected int                                             $floorIndex,
        protected int                                             $attempts = 0,
        private ?ProcessRouteFloorThumbnailCustomLoggingInterface $log = null
    ) {
        // Not passed as a constructor parameter since it's not serializable
        $this->thumbnailService = app()->make(ThumbnailServiceInterface::class);
        $this->queue            = sprintf('%s-%s-thumbnail-api', config('app.type'), config('app.env'));
        $this->log              = $log ?? app()->make(ProcessRouteFloorThumbnailLoggingInterface::class);
    }

    /**
     * @throws Exception
     */
    public function handle(): void
    {
        try {
            $this->log->handleStart(
                $this->dungeonRoute->public_key,
                $this->floorIndex,
                $this->dungeonRoute->id,
                $this->attempts,
                $this->dungeonRouteThumbnailJob->viewport_width,
                $this->dungeonRouteThumbnailJob->viewport_height,
                $this->dungeonRouteThumbnailJob->image_width,
                $this->dungeonRouteThumbnailJob->image_height,
                $this->dungeonRouteThumbnailJob->zoom_level,
                $this->dungeonRouteThumbnailJob->quality
            );

            if ((int)config('keystoneguru.thumbnail.max_attempts') > $this->attempts) {
                $result = $this->thumbnailService->createThumbnailCustom(
                    $this->dungeonRoute,
                    $this->floorIndex,
                    $this->attempts,
                    $this->dungeonRouteThumbnailJob->viewport_width,
                    $this->dungeonRouteThumbnailJob->viewport_height,
                    $this->dungeonRouteThumbnailJob->image_width,
                    $this->dungeonRouteThumbnailJob->image_height,
                    $this->dungeonRouteThumbnailJob->zoom_level,
                    $this->dungeonRouteThumbnailJob->quality
                );

                if (!$result) {
                    $this->log->handleCreateCustomThumbnailError();

                    // If there were errors, try again
                    ProcessRouteFloorThumbnailCustom::dispatch(
                        $this->dungeonRouteThumbnailJob,
                        $this->dungeonRoute,
                        $this->floorIndex,
                        ++$this->attempts
                    );
                } else {
                    $this->log->handleFinishedProcessing();

                    $this->dungeonRouteThumbnailJob->update(['status' => DungeonRouteThumbnailJob::STATUS_COMPLETED]);
                }
            } else {
                $this->log->handleMaxAttemptsReached();

                $this->dungeonRouteThumbnailJob->update(['status' => DungeonRouteThumbnailJob::STATUS_ERROR]);
            }

        } finally {
            $this->log->handleEnd();
        }
    }
}
