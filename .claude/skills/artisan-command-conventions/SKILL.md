---
name: artisan-command-conventions
description: Conventions for Artisan commands in keystone.guru — the domain subfolder layout, custom base classes (SchedulerCommand, BaseCombatLogCommand, ...), shared traits, signature naming, handle() method injection, and how scheduling works in routes/console.php (env guards, Docker stdout output). Use when creating or modifying an Artisan command or changing the schedule. Not for exposing a command in the admin panel (admin-artisan-command) or generic Laravel console questions.
---

# Artisan Command Conventions

## Overview

~100 commands live in `app/Console/Commands/`, subfoldered by domain (`CombatLog`, `Dungeon`,
`Mapping`, `MDT`, `Localization`, `Release`, `Scheduler/{Metric,Thumbnail,Telemetry,...}`, ...).
Laravel 12 streamlined structure: commands are auto-discovered (no Kernel registration) and the
schedule lives in `routes/console.php`. Remember the project rule: never `php artisan make:` —
create files directly, and run artisan only inside Docker.

## Base classes — check the hierarchy before adding a command

| Base | Path | Provides |
|---|---|---|
| `SchedulerCommand` | `app/Console/Commands/Scheduler/SchedulerCommand.php` | `use SavesToInfluxDB`; `trackTime(callable): int` — Stopwatch-times the work, catches exceptions (→ `$this->error()` + exit 1), writes a `scheduler` InfluxDB point. All `Scheduler/*` commands extend it. |
| `BaseCombatLogCommand` | `app/Console/Commands/CombatLog/BaseCombatLogCommand.php` | `parseCombatLogRecursively()`, `removeFile()`; sub-base `BaseSplitCombatLogCommand`. Does real filesystem work (`unlink`, `glob`). |
| `BaseSyncCommand` | `app/Console/Commands/Localization/BaseSyncCommand.php` | Parent of `SyncNpcNames` / `SyncSpellNames`. |
| `SetupDatabase` | `app/Console/Commands/Database/SetupDatabase.php` | Parent of `SetupMainDatabase` / `SetupCombatLogDatabase`. |
| `Measurement` | `app/Console/Commands/Scheduler/Telemetry/Measurement/Measurement.php` | Parent of the telemetry measurements (`UserCount`, `QueueSize`, ...). |

Shared traits in `app/Console/Commands/Traits/`: `ExecutesShellCommands` (`shell($cmds)` wrapping
`shell_exec` + echo), `SavesToInfluxDB` (`savePointToInfluxDB(...)`, no-op unless
`config('influxdb.enabled')`), `ConvertsMDTStrings`.

## Command shape

`app/Console/Commands/Scheduler/DungeonRoute/UpdatePopularity.php` is canonical:

```php
class UpdatePopularity extends SchedulerCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dungeonroute:updatepopularity';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Updates the popularity of all public dungeon routes.';

    public function handle(DungeonRouteServiceInterface $dungeonRouteService): int
    {
        return $this->trackTime(function () use ($dungeonRouteService) {
            $dungeonRouteService->updatePopularity();
        });
    }
}
```

- Services are **method-injected into `handle()`** (not the constructor); `handle()` returns `int`.
- Output via `$this->info/comment/warn/error` — commands do **not** use the structured-logging
  companions (zero `LoggingInterface` usages under `app/Console/Commands`).
- Signature naming: `domain:verb`, all lowercase, verb unseparated (`dungeonroute:updatepopularity`,
  `mapping:save`, `mdt:stringtoroute`). Umbrella prefixes: legacy `keystoneguru:*` and newer
  `ksg:*` (`ksg:migrate`). Args/options: `{filePath}`, `{--force}`,
  `{--chunk=50000 : Rows per batch}` (description after the colon).

## Scheduling — `routes/console.php`

Uses the `Schedule::` facade; entries are collected into a `$commands` array and post-processed:

```php
$commands[] = Schedule::command('dungeonroute:updaterating')->everyFifteenMinutes();
$commands[] = Schedule::command('affixgroupeasetiers:refresh')->cron('0 */8 * * *');
$commands[] = Schedule::command('dungeonroute:touch', ['teamId' => config('keystoneguru.raider_io.team_id')])->weeklyOn(3, '0');

foreach ($commands as $command) {
    $command->appendOutputTo('/proc/1/fd/1'); // all output to Docker stdout
}
```

- **Environment guards are plain PHP `if`s** (`!app()->environment('local')`,
  `app()->environment('local', 'testing')`, `$appType === 'production'`, `!$debug`) — the
  project does not use `->environments()`.
- **No `onOneServer()` / `withoutOverlapping()` / `runInBackground()` anywhere** — don't
  introduce them casually; match the existing plain style.
- New scheduled entries must be added to the `$commands` array (not scheduled standalone) so
  they get the `appendOutputTo` treatment.

## Gotchas

- `SchedulerCommand::trackTime()` swallows exceptions (converts to exit code 1) — a scheduled
  failure won't throw or alert by itself; log meaningfully inside the closure.
- Several distinct command classes share the short name `Cache` (`Scheduler/View/Cache`,
  `Scheduler/Discover/Cache`, ...) — always check the namespace you're importing.
- InfluxDB writes silently no-op unless `config('influxdb.enabled')` — telemetry "not working"
  locally is usually just that.
- Commands extending other commands is normal here (see the base-class table); check what a
  sibling command extends before defaulting to `Illuminate\Console\Command`.

## Related skills

- **admin-artisan-command** — exposing a command in the admin panel with a progress UI
- **creating-a-service** — the services commands inject into `handle()`
- **structured-logging** — used by services, not by commands directly
- **project-backend-structure** — the wider console/domain layout
