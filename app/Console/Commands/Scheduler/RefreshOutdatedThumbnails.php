<?php

namespace App\Console\Commands\Scheduler;

use App\Service\DungeonRoute\DungeonRouteServiceInterface;
use Exception;

class RefreshOutdatedThumbnails extends SchedulerCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scheduler:refreshoutdatedthumbnails';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refreshes outdated thumbnails for dungeonroutes';

    /**
     * Execute the console command.
     *
     *
     * @throws Exception
     */
    public function handle(DungeonRouteServiceInterface $dungeonRouteService): int
    {
        $this->trackTime(function () use ($dungeonRouteService) {
            $dungeonRouteService->refreshOutdatedThumbnails();
        });

        return 0;
    }
}
