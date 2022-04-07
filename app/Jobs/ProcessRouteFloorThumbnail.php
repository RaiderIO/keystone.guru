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

    /**
     * Create a new job instance.
     *
     * @param ThumbnailServiceInterface $thumbnailService
     * @param DungeonRoute $dungeonRoute
     * @param int $floorIndex
     */
    public function __construct(ThumbnailServiceInterface $thumbnailService, DungeonRoute $dungeonRoute, int $floorIndex)
    {
        $this->thumbnailService = $thumbnailService;
        $this->queue            = sprintf('%s-%s-thumbnail', config('app.type'), config('app.env'));
        $this->dungeonRoute     = $dungeonRoute;
        $this->floorIndex       = $floorIndex;
    }

    /**
     * @throws \Exception
     */
    public function handle()
    {
        Log::channel('scheduler')->info(sprintf('Started processing %s:%s', $this->dungeonRoute->public_key, $this->floorIndex));

        $this->thumbnailService->refreshThumbnail($this->dungeonRoute, $this->floorIndex);

        Log::channel('scheduler')->info(sprintf('Finished processing %s:%s', $this->dungeonRoute->public_key, $this->floorIndex));
    }
}
