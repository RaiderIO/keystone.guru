<?php

namespace App\Jobs;

use App\Models\DungeonRoute\DungeonRoute;
use App\Service\DungeonRoute\ThumbnailServiceInterface;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessRouteFloorThumbnail implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(protected ThumbnailServiceInterface $thumbnailService, protected DungeonRoute $dungeonRoute, protected int $floorIndex, protected int $attempts = 0)
    {
        $this->queue = sprintf('%s-%s-thumbnail', config('app.type'), config('app.env'));
    }

    /**
     * @throws Exception
     */
    public function handle(): void
    {
        Log::channel('scheduler')->info(
            sprintf('Started processing %s:%s (%d)', $this->dungeonRoute->public_key, $this->floorIndex, $this->dungeonRoute->id)
        );

        $result = false;
        if ((int)config('keystoneguru.thumbnail.max_attempts') > $this->attempts) {
            // Give some additional space since we're refreshing ALL floors - the first floor may get processed,
            // but the floors after that will otherwise think "oh the thumbnail is up-to-date" and not refresh.
            if ($this->dungeonRoute->thumbnail_updated_at->isBefore($this->dungeonRoute->updated_at->addHour())) {
                $result = $this->thumbnailService->createThumbnail($this->dungeonRoute, $this->floorIndex, $this->attempts);

                if (!$result) {
                    Log::channel('scheduler')->warning(sprintf('Error refreshing thumbnail, attempt %d', $this->attempts));

                    // If there were errors, try again
                    ProcessRouteFloorThumbnail::dispatch($this->thumbnailService, $this->dungeonRoute, $this->floorIndex, ++$this->attempts);
                }
            } else {
                Log::channel('scheduler')->warning('Not refreshing thumbnail - thumbnail is already up-to-date');
            }
        } else {
            Log::channel('scheduler')->warning(sprintf('Not refreshing thumbnail - max attempts of %d reached', $this->attempts));
        }

        Log::channel('scheduler')->info(
            sprintf('Finished processing %s:%s (%d) -> %d', $this->dungeonRoute->public_key, $this->floorIndex, $this->dungeonRoute->id, (int)$result)
        );
    }
}
