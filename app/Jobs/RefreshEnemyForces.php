<?php

namespace App\Jobs;

use App\Models\DungeonRoute\DungeonRoute;
use Exception;
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
        $this->dungeonRouteId = $dungeonRouteId;
    }

    /**
     * @throws Exception
     */
    public function handle()
    {
        $dungeonRoute = DungeonRoute::find($this->dungeonRouteId);
        if ($dungeonRoute) {
            Log::channel('scheduler')->info(
                sprintf('Refreshing enemy forces for route %s (%d)', $dungeonRoute->public_key, $this->dungeonRouteId)
            );
            $dungeonRoute->update(['enemy_forces' => $dungeonRoute->getEnemyForces()]);
        }
    }
}
