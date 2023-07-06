<?php

namespace App\Jobs;

use Artisan;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RefreshDiscoverCache implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @throws Exception
     */
    public function handle()
    {
        Log::channel('scheduler')->info('Started caching discover routes pages');

        Artisan::call('discover:cache');

        Log::channel('scheduler')->info('Finished caching discover routes pages');
    }
}
