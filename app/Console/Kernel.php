<?php

namespace App\Console;

use App\Console\Commands\GetCurrentRelease;
use App\Console\Commands\GetReleaseBody;
use App\Logic\Scheduler\FindOutdatedThumbnails;
use App\Logic\Scheduler\DeleteExpiredDungeonRoutes;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Log;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        GetCurrentRelease::class,
        GetReleaseBody::class
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        Log::channel('scheduler')->debug('Starting scheduler');
        $schedule->call(new FindOutdatedThumbnails)->everyFiveMinutes();
        $schedule->call(new DeleteExpiredDungeonRoutes)->hourly();
        Log::channel('scheduler')->debug('Finished scheduler');
    }

    /**
     * Register the Closure based commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        require base_path('routes/console.php');
    }
}
