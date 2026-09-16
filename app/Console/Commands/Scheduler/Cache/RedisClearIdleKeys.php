<?php

namespace App\Console\Commands\Scheduler\Cache;

use App\Console\Commands\Scheduler\SchedulerCommand;
use App\Service\Cache\CacheServiceInterface;

class RedisClearIdleKeys extends SchedulerCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'redis:clearidlekeys';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clears idle keys in redis';

    /**
     * Execute the console command.
     */
    public function handle(CacheServiceInterface $cacheService): int
    {
        return $this->trackTime(function () use ($cacheService) {
            $keysCleared = $cacheService->clearIdleKeys();
            $this->info(sprintf('Cleared %d keys', $keysCleared));

            return 0;
        });
    }
}
