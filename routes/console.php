<?php

use App\Console\Commands\Scheduler\Metric\Aggregate;
use App\Console\Commands\Scheduler\View\Cache;
use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of your Closure based console
| commands. Each Closure is bound to a command instance allowing a
| simple approach to interacting with each command's IO methods.
|
*/

$debug   = config('app.debug');
$appType = config('app.type');

$commands = [];

$commands[] = Schedule::command('dungeonroute:updatepopularity')->hourly();
$commands[] = Schedule::command('dungeonroute:updaterating')->everyFifteenMinutes();

$commands[] = Schedule::command('dungeonroute:refreshoutdatedthumbnails')->everyFifteenMinutes();
$commands[] = Schedule::command('dungeonroute:deleteexpired')->hourly();
$commands[] = Schedule::command('dungeonroute:touch', ['teamId' => config('keystoneguru.raider_io.team_id')])->weeklyOn(3, '0');

if (in_array($appType, [
    'mapping',
    'local',
])) {
    $commands[] = Schedule::command('mapping:sync')->everyFiveMinutes();

    // Ensure display IDs are set
    $commands[] = Schedule::command('wowhead:refreshdisplayids')->hourly();
}

$commands[] = Schedule::command('affixgroupeasetiers:refresh')->cron('0 */8 * * *'); // Every 8 hours

// https://laravel.com/docs/8.x/horizon
if (app()->environment('local', 'testing')) {
    $commands[] = Schedule::command('horizon:snapshot')->everyFiveMinutes();
}

if ($appType === 'production') {
    $commands[] = Schedule::command('scheduler:telemetry')->everyFiveMinutes();
}

// https://laravel.com/docs/8.x/telescope#data-pruning
$commands[] = Schedule::command('telescope:prune --hours=48')->daily();

// Refresh any membership status - if they're unsubbed, revoke their access. If they're subbed, add access
$commands[] = Schedule::command('patreon:refreshmembers')->hourly();

// We don't want the cache when we're debugging to ensure fresh data every time
if (!$debug) {
    $commands[] = Schedule::command('discover:cache')->everyTwoHours();
    $commands[] = Schedule::command('keystoneguru:view cache')->everyTenMinutes();
}

// Ensure redis remains healthy
$commands[] = Schedule::command('redis:clearidlekeys 900')->everyFifteenMinutes();

// Aggregate all metrics so they're nice and snappy to load
$commands[] = Schedule::command('metric:aggregate')->everyFiveMinutes();
$commands[] = Schedule::command('metric:savepending')->everyMinute();

// Cleanup the generated custom thumbnails
$commands[] = Schedule::command('thumbnail:deleteexpiredjobs')->everyFifteenMinutes();

foreach ($commands as $command) {
    // php://stdout is used to ensure that the output is always logged, even when running in a Docker container
    $command->appendOutputTo('/proc/1/fd/1');
}
