<?php

namespace App\Jobs;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonRoute\DungeonRouteThumbnailJob;
use App\Service\DungeonRoute\ThumbnailServiceInterface;
use Illuminate\Support\Facades\Log;

class ProcessRouteFloorThumbnailCustom extends ProcessRouteFloorThumbnail
{

    private DungeonRouteThumbnailJob $dungeonRouteThumbnailJob;
    private ?int                     $width;
    private ?int                     $height;
    private ?int                     $quality;

    /**
     * Create a new job instance.
     *
     * @param ThumbnailServiceInterface $thumbnailService
     * @param DungeonRouteThumbnailJob  $dungeonRouteThumbnailJob
     * @param DungeonRoute              $dungeonRoute
     * @param int                       $floorIndex
     * @param int                       $attempts
     * @param int|null                  $width
     * @param int|null                  $height
     * @param int|null                  $quality
     */
    public function __construct(
        ThumbnailServiceInterface $thumbnailService,
        DungeonRouteThumbnailJob  $dungeonRouteThumbnailJob,
        DungeonRoute              $dungeonRoute,
        int                       $floorIndex,
        int                       $attempts = 0,
        ?int                      $width = null,
        ?int                      $height = null,
        ?int                      $quality = null
    ) {
        parent::__construct($thumbnailService, $dungeonRoute, $floorIndex, $attempts);

        $this->queue                    = sprintf('%s-%s-thumbnail-api', config('app.type'), config('app.env'));
        $this->dungeonRouteThumbnailJob = $dungeonRouteThumbnailJob;
        $this->width                    = $width;
        $this->height                   = $height;
        $this->quality                  = $quality;
    }

    /**
     * @throws \Exception
     */
    public function handle()
    {
        Log::channel('scheduler')->info(
            sprintf(
                'Started processing custom thumbnail %s:%s (%d, %d, %d, %d)',
                $this->dungeonRoute->public_key,
                $this->floorIndex,
                $this->dungeonRoute->id,
                $this->width ?? -1,
                $this->height ?? -1,
                $this->quality ?? -1
            )
        );

        if ((int)config('keystoneguru.thumbnail.max_attempts') > $this->attempts) {
            $result = $this->thumbnailService->createThumbnailCustom(
                $this->dungeonRoute,
                $this->floorIndex,
                $this->attempts,
                $this->width,
                $this->height,
                $this->quality
            );

            if (!$result) {
                Log::channel('scheduler')->warning(sprintf('Error refreshing thumbnail, attempt %d', $this->attempts));

                // If there were errors, try again
                ProcessRouteFloorThumbnailCustom::dispatch(
                    $this,
                    $this->dungeonRoute,
                    $this->floorIndex,
                    ++$this->attempts,
                    $this->width,
                    $this->height,
                    $this->quality
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
