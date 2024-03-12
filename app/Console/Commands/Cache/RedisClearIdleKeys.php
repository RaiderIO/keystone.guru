<?php

namespace App\Console\Commands\Cache;

use App\Service\Cache\CacheServiceInterface;
use Illuminate\Console\Command;

class RedisClearIdleKeys extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'redis:clearidlekeys {seconds=3600}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clears all idle keys in redis for Laravel Model Cache that have not been accessed in a specific time in seconds';

    /**
     * Execute the console command.
     */
    public function handle(CacheServiceInterface $cacheService): int
    {
        $seconds = (int)$this->argument('seconds');

        $cacheService->clearIdleKeys($seconds);

        return 0;
    }
}
