<?php

namespace App\Jobs;

use App\Models\DungeonRoute\DungeonRoute;
use App\Service\DungeonRoute\DungeonRouteServiceInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UpgradeDungeonRouteMappingVersion implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(protected DungeonRoute $dungeonRoute)
    {
    }

    public function handle(): void
    {
        $dungeonRouteService = app()->make(DungeonRouteServiceInterface::class);
        $dungeonRouteService->upgradeMappingVersion($this->dungeonRoute);
    }
}
