<?php

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

$commands[] = Schedule::command('combatlog:detectstaledata')->hourly();
//if (in_array($appType, [
//    'production',
//])) {
//    $commands[] = Schedule::command('combatlog:pollruns')->hourly();
//}

$commands[] = Schedule::command('dungeonroute:updatepopularity')->hourly();
$commands[] = Schedule::command('dungeonroute:updaterating')->everyFifteenMinutes();

$commands[] = Schedule::command('dungeonroute:deleteexpired')->hourly();
$commands[] = Schedule::command('dungeonroute:publishscheduled')->everyFiveMinutes();
$commands[] = Schedule::command('dungeonroute:touch', ['teamId' => config('keystoneguru.raider_io.team_id')])->weeklyOn(3, '0');

// If thumbnails are needed locally, move this command
if (!app()->environment('local')) {
    $commands[] = Schedule::command('dungeonroute:refreshoutdatedthumbnails')->everyFifteenMinutes();
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

$commands[] = Schedule::command('page-views:prune')->daily();

// Refresh any membership status - if they're unsubbed, revoke their access. If they're subbed, add access
$commands[] = Schedule::command('patreon:refreshmembers')->hourly();

// We don't want the cache when we're debugging to ensure fresh data every time
if (!$debug) {
    $commands[] = Schedule::command('discover:cache')->everyTwoHours();
    $commands[] = Schedule::command('keystoneguru:view cache')->everyTenMinutes();
}

// Ensure redis remains healthy
$commands[] = Schedule::command('redis:clearidlekeys 900')->everyFifteenMinutes();
$commands[] = Schedule::command('modelCache:clear')->daily();

// Aggregate all metrics so they're nice and snappy to load
$commands[] = Schedule::command('metric:aggregate')->everyFiveMinutes();
$commands[] = Schedule::command('metric:savepending')->everyMinute();

// Cleanup the generated custom thumbnails
$commands[] = Schedule::command('thumbnail:deleteexpiredjobs')->everyFifteenMinutes();

// Keep the wide hero-band thumbnails fresh for the routes shown as heroes on the discovery pages.
// Rendering needs headless chrome, so skip it locally like the other thumbnail refreshers.
if (!app()->environment('local')) {
    $commands[] = Schedule::command('thumbnail:ensureheroes')->hourly();
}

// PID 1's stdout is used to ensure that the output is always logged, even when running in a Docker
// container. When the scheduler runs as a non-root user (local dev cron runs it as ksg, #3414) it
// cannot open /proc/1/fd/1 (owned by root), so fall back to the process's own stdout — the local
// cron.d entry already appends that to /var/log/cron.log, which PID 1 tails to Docker's stdout.
$schedulerOutputPath = is_writable('/proc/1/fd/1') ? '/proc/1/fd/1' : '/dev/stdout';

foreach ($commands as $command) {
    $command->appendOutputTo($schedulerOutputPath);
}
