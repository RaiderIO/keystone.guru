<?php

namespace App\Console\Commands\View;

use App\Logic\Utils\Stopwatch;
use App\Models\GameServerRegion;
use App\Service\View\ViewServiceInterface;
use Illuminate\Console\Command;

class Cache extends Command
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
     * Create a new command instance.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(ViewServiceInterface $viewService): int
    {
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
        }

        return 0;
    }
}
