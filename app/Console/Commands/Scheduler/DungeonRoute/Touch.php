<?php

namespace App\Console\Commands\Scheduler\DungeonRoute;

use App\Console\Commands\Scheduler\SchedulerCommand;
use App\Service\DungeonRoute\DungeonRouteServiceInterface;

class Touch extends SchedulerCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dungeonroute:touch {teamId}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Updates the updated at of specific dungeon routes (TEMP).";

    public function handle(
        DungeonRouteServiceInterface $dungeonRouteService,
    ): int {
        $teamId = (int)$this->argument('teamId');

        return $this->trackTime(function () use ($dungeonRouteService, $teamId) {
            $dungeonRouteService->touchRoutesForTeam($teamId);
        });
    }
}
