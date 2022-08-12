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

class RefreshEnemyForces implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** @var int $dungeonRouteId */
    private int $dungeonRouteId;

    /**
     * Create a new job instance.
     *
     * @param int $dungeonRouteId
     */
    public function __construct(int $dungeonRouteId)
    {
        $this->queue          = sprintf('%s-%s-thumbnail', config('app.type'), config('app.env'));
        $this->dungeonRouteId = $dungeonRouteId;
    }

    /**
     * @throws \Exception
     */
    public function handle()
    {
        Log::channel('scheduler')->info(sprintf('Started processing %s', $this->dungeonRouteId));

        $dungeonRoute = DungeonRoute::find($this->dungeonRouteId);
        if ($dungeonRoute) {
            $dungeonRoute->update(['enemy_forces' => $dungeonRoute->getEnemyForces()]);
        }

        Log::channel('scheduler')->info(sprintf('Finished processing %s', $this->dungeonRouteId));
    }
}
