<?php

namespace App\Console\Commands\Scheduler\DungeonRoute;

use App\Console\Commands\Scheduler\SchedulerCommand;
use App\Service\DungeonRoute\DungeonRouteServiceInterface;

class UpdatePopularity extends SchedulerCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dungeonroute:updatepopularity';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Updates the popularity of all public dungeon routes.";

    public function handle(
        DungeonRouteServiceInterface $dungeonRouteService
    ): int {
        return $this->trackTime(function () use ($dungeonRouteService) {
            $dungeonRouteService->updatePopularity();
        });
    }
}
