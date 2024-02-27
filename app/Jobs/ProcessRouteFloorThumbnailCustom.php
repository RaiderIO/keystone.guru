<?php

namespace App\Jobs;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonRoute\DungeonRouteThumbnailJob;
use App\Service\DungeonRoute\ThumbnailServiceInterface;
use Exception;
use Illuminate\Support\Facades\Log;

class ProcessRouteFloorThumbnailCustom extends ProcessRouteFloorThumbnail
{
    /**
     * Create a new job instance.
     */
    public function __construct(
        ThumbnailServiceInterface $thumbnailService,
        private readonly DungeonRouteThumbnailJob $dungeonRouteThumbnailJob,
        DungeonRoute $dungeonRoute,
        int $floorIndex,
        int $attempts = 0
    ) {
        parent::__construct($thumbnailService, $dungeonRoute, $floorIndex, $attempts);

        $this->queue = sprintf('%s-%s-thumbnail-api', config('app.type'), config('app.env'));
    }

    /**
     * @throws Exception
     */
    public function handle(): void
    {
        Log::channel('scheduler')->info(
            sprintf(
                'Started processing custom thumbnail %s:%s (%d, %d, %d, %d, %d, %d, %d)',
                $this->dungeonRoute->public_key,
                $this->floorIndex,
                $this->dungeonRoute->id,
                $this->dungeonRouteThumbnailJob->viewport_width ?? -1,
                $this->dungeonRouteThumbnailJob->viewport_height ?? -1,
                $this->dungeonRouteThumbnailJob->image_width ?? -1,
                $this->dungeonRouteThumbnailJob->image_height ?? -1,
                $this->dungeonRouteThumbnailJob->zoom_level ?? -1,
                $this->dungeonRouteThumbnailJob->quality ?? -1
            )
        );

        if ((int) config('keystoneguru.thumbnail.max_attempts') > $this->attempts) {
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

            if (! $result) {
                Log::channel('scheduler')->warning(sprintf('Error refreshing thumbnail, attempt %d', $this->attempts));

                // If there were errors, try again
                ProcessRouteFloorThumbnailCustom::dispatch(
                    $this->thumbnailService,
                    $this->dungeonRouteThumbnailJob,
                    $this->dungeonRoute,
                    $this->floorIndex,
                    ++$this->attempts
                );
            } else {
                Log::channel('scheduler')->info(
                    sprintf('Finished processing custom thumbnail %s:%s (%d)', $this->dungeonRoute->public_key, $this->floorIndex, $this->dungeonRoute->id)
                );

                $this->dungeonRouteThumbnailJob->update(['status' => DungeonRouteThumbnailJob::STATUS_COMPLETED]);
            }
        } else {
            Log::channel('scheduler')->warning(sprintf('Not generating custom thumbnail - max attempts of %d reached', $this->attempts));

            $this->dungeonRouteThumbnailJob->update(['status' => DungeonRouteThumbnailJob::STATUS_ERROR]);
        }
    }
}
