<?php

namespace App\Console\Commands\Discover;

use App\Service\Cache\CacheService;
use App\Service\DungeonRoute\DiscoverService;
use Illuminate\Console\Command;
use Psr\SimpleCache\InvalidArgumentException;

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
     * @param CacheService $cacheService
     * @return int
     * @throws InvalidArgumentException
     */
    public function handle(DiscoverService $discoverService, CacheService $cacheService)
    {
        // Refresh caches for all categories
//        $popular = $discoverService->popular();
//        $cacheService->set(
//            config('keystoneguru.discover.service.popular.cache_key'),
//            $popular,
//            config('keystoneguru.discover.service.popular.ttl'),
//        );

        return 0;
    }
}
