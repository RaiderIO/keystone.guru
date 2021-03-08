<?php

namespace App\Console\Commands\Discover;

use App\Console\Commands\Traits\ExecutesShellCommands;
use App\Service\DungeonRoute\DiscoverService;
use Illuminate\Console\Command;

class Cache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'discover:cache';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Caches all search results for routes for the route discovery page';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @param DiscoverService $discoverService
     * @return int
     */
    public function handle(DiscoverService $discoverService)
    {
        $discoverService->dropCaches();
        $discoverService->popular();

        return 0;
    }
}
