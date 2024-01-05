<?php

namespace App\Jobs;

use App\Models\DungeonRoute;
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

    /** @var ThumbnailServiceInterface */
    private ThumbnailServiceInterface $thumbnailService;

    /** @var DungeonRoute $dungeonRoute */
    private DungeonRoute $dungeonRoute;

    /** @var int $floorIndex */
    private int $floorIndex;

    private int $attempts;

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
        $this->thumbnailService = $thumbnailService;
        $this->queue            = sprintf('%s-%s-thumbnail', config('app.type'), config('app.env'));
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
            $this->thumbnailService->refreshThumbnail($this->dungeonRoute, $this->floorIndex, $this->attempts);
        } else {
            Log::channel('scheduler')->warning(sprintf('Not refreshing thumbnail - max attempts of %d reached', $this->attempts));
        }

        Log::channel('scheduler')->info(
            sprintf('Finished processing %s:%s (%d)', $this->dungeonRoute->public_key, $this->floorIndex, $this->dungeonRoute->id)
        );
    }
}
