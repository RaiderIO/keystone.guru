<?php

namespace App\Console\Commands\Scheduler\DungeonRoute;

use App\Console\Commands\Scheduler\SchedulerCommand;
use App\Service\DungeonRoute\DungeonRouteServiceInterface;
use Exception;

class DeleteExpired extends SchedulerCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dungeonroute:deleteexpired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Deletes all routes that have expired ';

    /**
     * Execute the console command.
     *
     *
     * @throws Exception
     */
    public function handle(DungeonRouteServiceInterface $dungeonRouteService): int
    {
        return $this->trackTime(function () use ($dungeonRouteService) {
            $dungeonRouteService->deleteExpiredDungeonRoutes();
        });
    }
}
