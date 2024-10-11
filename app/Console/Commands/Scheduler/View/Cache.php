<?php

namespace App\Console\Commands\Scheduler\View;

use App\Console\Commands\Scheduler\SchedulerCommand;
use App\Logic\Utils\Stopwatch;
use App\Models\GameServerRegion;
use App\Service\View\ViewServiceInterface;

class Cache extends SchedulerCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'keystoneguru:view {operation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Caches all search results for routes for the route discovery page';

    /**
     * Execute the console command.
     */
    public function handle(ViewServiceInterface $viewService): int
    {
        return $this->trackTime(function () use ($viewService) {
            $operation = $this->argument('operation');

            if ($operation === 'cache') {
                $this->info('Caching view variables...');

                Stopwatch::start('cache');

                // This caches the data that is used in all views
                $viewService->getGlobalViewVariables(false);

                foreach (GameServerRegion::all() as $gameServerRegion) {
                    $viewService->getGameServerRegionViewVariables($gameServerRegion, false);
                }

                $this->info(sprintf('Successfully cached in %sms', Stopwatch::elapsed('cache')));
            } else {
                $this->error('No operation passed!');
            }

            return 0;
        });
    }
}
