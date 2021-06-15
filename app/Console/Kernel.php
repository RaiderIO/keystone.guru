<?php

namespace App\Console;

use App\Console\Commands\CreateGithubRelease;
use App\Console\Commands\CreateGithubReleaseTicket;
use App\Console\Commands\Discover\Cache;
use App\Console\Commands\Environment\Update as EnvironmentUpdate;
use App\Console\Commands\Environment\UpdatePrepare as EnvironmentUpdatePrepare;
use App\Console\Commands\Handlebars\Refresh as HandlebarsRefresh;
use App\Console\Commands\Mapping\Commit as MappingCommit;
use App\Console\Commands\Mapping\Merge as MappingMerge;
use App\Console\Commands\Mapping\Restore as MappingRestore;
use App\Console\Commands\Mapping\Save as MappingSave;
use App\Console\Commands\MDT\Decode;
use App\Console\Commands\MDT\Encode;
use App\Console\Commands\Release\GetCurrentRelease;
use App\Console\Commands\Release\GetReleaseBody;
use App\Console\Commands\Release\ReportRelease;
use App\Console\Commands\Release\Save as ReleaseSave;
use App\Console\Commands\Scheduler\DeleteExpiredDungeonRoutes;
use App\Console\Commands\Scheduler\RefreshAffixGroupEaseTiers;
use App\Console\Commands\Scheduler\Telemetry\Telemetry;
use App\Console\Commands\StartSupervisor;
use App\Console\Commands\Test;
use App\Logic\Scheduler\RefreshOutdatedThumbnails;
use App\Logic\Scheduler\SynchronizeMapping;
use App\Logic\Scheduler\UpdateDungeonRoutePopularity;
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
        CreateGithubRelease::class,
        CreateGithubReleaseTicket::class,
        StartSupervisor::class,

        // Discover
        Cache::class,

        // Environment
        EnvironmentUpdatePrepare::class,
        EnvironmentUpdate::class,

        // Handlebars
        HandlebarsRefresh::class,

        // Mapping
        MappingCommit::class,
        MappingMerge::class,
        MappingSave::class,
        MappingRestore::class,

        // MDT
        Encode::class,
        Decode::class,

        // Release
        GetCurrentRelease::class,
        GetReleaseBody::class,
        ReportRelease::class,
        ReleaseSave::class,

        // Scheduler
        RefreshAffixGroupEaseTiers::class,
        Telemetry::class,
        DeleteExpiredDungeonRoutes::class,

        // Test
        Test::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param Schedule $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        Log::channel('scheduler')->debug('Starting scheduler');

        $appType = config('app.type');

        $schedule->call(new UpdateDungeonRoutePopularity)->hourly();
        $schedule->call(new RefreshOutdatedThumbnails)->everyFiveMinutes();
        $schedule->command('scheduler:deleteexpired')->hourly();
        if ($appType === 'mapping') {
            $schedule->call(new SynchronizeMapping)->everyFiveMinutes();
        }
        $schedule->command('affixgroupeasetiers:refresh')->cron('0 */8 * * *'); // Every 8 hours

        // https://laravel.com/docs/8.x/horizon
        $schedule->command('horizon:snapshot')->everyFiveMinutes();

        if ($appType === 'local' || $appType === 'live') {
            $schedule->command('scheduler:telemetry')->everyFiveMinutes();
        }

        // https://laravel.com/docs/8.x/telescope#data-pruning
        $schedule->command('telescope:prune --hours=48')->daily();
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
