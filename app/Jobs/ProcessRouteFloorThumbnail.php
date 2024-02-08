<?php

namespace App\Jobs;

use App\Models\DungeonRoute\DungeonRoute;
use App\Service\DungeonRoute\ThumbnailServiceInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessRouteFloorThumbnail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected ThumbnailServiceInterface $thumbnailService;

    protected DungeonRoute $dungeonRoute;

    protected int $floorIndex;

    protected int $attempts;

    /**
     * Create a new job instance.
     *
     * @param ThumbnailServiceInterface $thumbnailService
     * @param DungeonRoute              $dungeonRoute
     * @param int                       $floorIndex
     * @param int                       $attempts
     */
    public function __construct(ThumbnailServiceInterface $thumbnailService, DungeonRoute $dungeonRoute, int $floorIndex, int $attempts = 0)
    {
        $this->queue            = sprintf('%s-%s-thumbnail', config('app.type'), config('app.env'));
        $this->thumbnailService = $thumbnailService;
        $this->dungeonRoute     = $dungeonRoute;
        $this->floorIndex       = $floorIndex;
        $this->attempts         = $attempts;
    }

    /**
     * @throws \Exception
     */
    public function handle()
    {
        Log::channel('scheduler')->info(
            sprintf('Started processing %s:%s (%d)', $this->dungeonRoute->public_key, $this->floorIndex, $this->dungeonRoute->id)
        );

        if ((int)config('keystoneguru.thumbnail.max_attempts') > $this->attempts) {
            $result = $this->thumbnailService->createThumbnail($this->dungeonRoute, $this->floorIndex, $this->attempts);

            if (!$result) {
                Log::channel('scheduler')->warning(sprintf('Error refreshing thumbnail, attempt %d', $this->attempts));

                // If there were errors, try again
                ProcessRouteFloorThumbnail::dispatch($this, $this->dungeonRoute, $this->floorIndex, ++$this->attempts);
            } else {
                Log::channel('scheduler')->info(
                    sprintf('Finished processing %s:%s (%d)', $this->dungeonRoute->public_key, $this->floorIndex, $this->dungeonRoute->id)
                );
            }
        } else {
            Log::channel('scheduler')->warning(sprintf('Not refreshing thumbnail - max attempts of %d reached', $this->attempts));
        }
    }
}
