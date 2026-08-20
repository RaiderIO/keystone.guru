<?php

namespace App\Jobs;

use App\Service\Cache\CacheServiceInterface;
use Artisan;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class DropCaches implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout = 1800;

    public function __construct()
    {
        $this->queue = sprintf('%s-long-running', config('app.type'));
    }

    public function handle(CacheServiceInterface $cacheService): void
    {
        Log::channel('scheduler')->info('Started dropping caches');

        $cacheService->dropCaches();

        Artisan::call('modelCache:clear');

        Artisan::call('keystoneguru:view', ['operation' => 'cache']);

        Log::channel('scheduler')->info('Finished dropping caches');
    }
}
