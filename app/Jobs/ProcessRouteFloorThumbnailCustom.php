<?php

namespace App\Jobs;

use App\Jobs\Logging\ProcessRouteFloorThumbnailCustomLoggingInterface;
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

    /**
     * Create a new job instance.
     */
    public function __construct(
        private readonly DungeonRouteThumbnailJob $dungeonRouteThumbnailJob,
        protected DungeonRoute                    $dungeonRoute,
        protected int                             $floorIndex,
        protected int                             $attempts = 0,
    ) {
        // Not passed as a constructor parameter since it's not serializable
        $this->queue = sprintf('%s-%s-thumbnail-api', config('app.type'), config('app.env'));
    }

    /**
     * @throws Exception
     */
    public function handle(): void
    {
        // Cannot serialize these objects - so we have to create them here
        $thumbnailService = app()->make(ThumbnailServiceInterface::class);
        $log              = app()->make(ProcessRouteFloorThumbnailCustomLoggingInterface::class);

        try {
            $log->handleStart(
                $this->dungeonRoute->public_key,
                $this->floorIndex,
                $this->dungeonRoute->id,
                $this->attempts,
                $this->dungeonRouteThumbnailJob->viewport_width,
                $this->dungeonRouteThumbnailJob->viewport_height,
                $this->dungeonRouteThumbnailJob->image_width,
                $this->dungeonRouteThumbnailJob->image_height,
                $this->dungeonRouteThumbnailJob->zoom_level,
                $this->dungeonRouteThumbnailJob->quality,
            );

            if ((int)config('keystoneguru.thumbnail.max_attempts') > $this->attempts) {
                $result = $thumbnailService->createThumbnailCustom(
                    $this->dungeonRoute,
                    $this->floorIndex,
                    $this->attempts,
                    $this->dungeonRouteThumbnailJob->viewport_width,
                    $this->dungeonRouteThumbnailJob->viewport_height,
                    $this->dungeonRouteThumbnailJob->image_width,
                    $this->dungeonRouteThumbnailJob->image_height,
                    $this->dungeonRouteThumbnailJob->zoom_level,
                    $this->dungeonRouteThumbnailJob->quality,
                );

                if (!$result) {
                    $log->handleCreateCustomThumbnailError();

                    // If there were errors, try again
                    ProcessRouteFloorThumbnailCustom::dispatch(
                        $this->dungeonRouteThumbnailJob,
                        $this->dungeonRoute,
                        $this->floorIndex,
                        ++$this->attempts,
                    );
                } else {
                    $log->handleFinishedProcessing();

                    $this->dungeonRouteThumbnailJob->update([
                        'file_id' => $result->file_id,
                        'status'  => DungeonRouteThumbnailJob::STATUS_COMPLETED,
                    ]);
                }
            } else {
                $log->handleMaxAttemptsReached();

                $this->dungeonRouteThumbnailJob->update(['status' => DungeonRouteThumbnailJob::STATUS_ERROR]);
            }
        } finally {
            $log->handleEnd();
        }
    }
}
