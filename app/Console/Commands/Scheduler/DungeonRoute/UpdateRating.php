<?php

namespace App\Console\Commands\Scheduler\DungeonRoute;

use App\Console\Commands\Scheduler\SchedulerCommand;
use App\Service\DungeonRoute\DungeonRouteServiceInterface;

class UpdateRating extends SchedulerCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dungeonroute:updaterating';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Updates the rating of all dungeon routes.";

    public function handle(
        DungeonRouteServiceInterface $dungeonRouteService,
    ): int {
        return $this->trackTime(function () use ($dungeonRouteService) {
            $dungeonRouteService->updateRating();
        });
    }
}
