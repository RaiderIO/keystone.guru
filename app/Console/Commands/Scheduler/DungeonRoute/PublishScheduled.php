<?php

namespace App\Console\Commands\Scheduler\DungeonRoute;

use App\Console\Commands\Scheduler\SchedulerCommand;
use App\Service\DungeonRoute\DungeonRouteServiceInterface;
use Exception;

class PublishScheduled extends SchedulerCommand
{
    protected $signature = 'dungeonroute:publishscheduled';

    protected $description = 'Publishes all dungeon routes that have a scheduled publish date that has passed.';

    /**
     * @throws Exception
     */
    public function handle(DungeonRouteServiceInterface $dungeonRouteService): int
    {
        return $this->trackTime(function () use ($dungeonRouteService) {
            $dungeonRouteService->publishScheduledDungeonRoutes();
        });
    }
}
