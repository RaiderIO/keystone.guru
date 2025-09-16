<?php

use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Console\Commands\WowTools\RefreshDisplayIds;
use App\Console\Commands\Wowhead\RefreshDisplayIds as RefreshDisplayIdsWowhead;
use App\Console\Commands\Wowhead\FetchSpellData;
use App\Console\Commands\Wowhead\FetchMissingSpells;
use App\Console\Commands\Wowhead\FetchMissingSpellIcons;
use App\Console\Commands\Wowhead\FetchHealth;
use App\Console\Commands\Wowhead\FetchDisplayIds;
use App\Console\Commands\Supervisor\StopSupervisor;
use App\Console\Commands\Supervisor\StartSupervisor;
use App\Console\Commands\Spell\ImportCsv;
use App\Console\Commands\Spell\ExportCsv;
use App\Console\Commands\Scheduler\View\Cache;
use App\Console\Commands\Scheduler\Thumbnail\DeleteExpiredJobs;
use App\Console\Commands\Scheduler\Telemetry\Telemetry;
use App\Console\Commands\Scheduler\RefreshAffixGroupEaseTiers;
use App\Console\Commands\Scheduler\Patreon\RefreshMembershipStatus;
use App\Console\Commands\Scheduler\Metric\SavePending;
use App\Console\Commands\Scheduler\Metric\Aggregate;
use App\Console\Commands\Scheduler\DungeonRoute\UpdateRating;
use App\Console\Commands\Scheduler\DungeonRoute\UpdatePopularity;
use App\Console\Commands\Scheduler\DungeonRoute\Touch;
use App\Console\Commands\Scheduler\DungeonRoute\RefreshOutdatedThumbnails;
use App\Console\Commands\Scheduler\DungeonRoute\DeleteExpired;
use App\Console\Commands\Scheduler\Discover\Cache as DiscoverCache;
use App\Console\Commands\Scheduler\Cache\RedisClearIdleKeys;
use App\Console\Commands\Scheduler\AdProvider\SyncAdsTxt;
use App\Console\Commands\Release\Success as ReleaseSuccess;
use App\Console\Commands\Release\Save as ReleaseSave;
use App\Console\Commands\Release\Report as ReleaseReport;
use App\Console\Commands\Release\GetCurrent as ReleaseGetCurrent;
use App\Console\Commands\Release\GetBody as ReleaseGetBody;
use App\Console\Commands\Release\Export as ReleaseExport;
use App\Console\Commands\ReadOnlyMode\Enable as EnableReadOnlyMode;
use App\Console\Commands\ReadOnlyMode\Disable as DisableReadOnlyMode;
use App\Console\Commands\Random;
use App\Console\Commands\MDT\ImportSpells;
use App\Console\Commands\MDT\ImportNpcs;
use App\Console\Commands\MDT\ImportMapping;
use App\Console\Commands\MDT\ExportMapping;
use App\Console\Commands\MDT\Encode;
use App\Console\Commands\MDT\Decode;
use App\Console\Commands\Mapping\Sync as MappingSync;
use App\Console\Commands\Mapping\Save as MappingSave;
use App\Console\Commands\Mapping\RotateIngameCoords;
use App\Console\Commands\Mapping\Restore as MappingRestore;
use App\Console\Commands\Mapping\Merge as MappingMerge;
use App\Console\Commands\Mapping\Copy as MappingCopy;
use App\Console\Commands\Mapping\Commit as MappingCommit;
use App\Console\Commands\Mapping\AssignPackGroups;
use App\Console\Commands\Mapping\AssignMDTIDs;
use App\Console\Commands\MapIcon\GenerateItemIcons;
use App\Console\Commands\Localization\Zone\SyncZoneNames;
use App\Console\Commands\Localization\Validation\ConvertLocalizations as ValidationConvertLocalizations;
use App\Console\Commands\Localization\Spell\SyncSpellNames;
use App\Console\Commands\Localization\Spell\ImportSpellNames;
use App\Console\Commands\Localization\Spell\ExportSpellNames;
use App\Console\Commands\Localization\Npc\SyncNpcNames;
use App\Console\Commands\Localization\Npc\ImportNpcNames;
use App\Console\Commands\Localization\Npc\ExportNpcNames;
use App\Console\Commands\Localization\LocalizationSync;
use App\Console\Commands\Localization\Larex\WriteKsgFromCrowdin;
use App\Console\Commands\Localization\Larex\WriteKsg;
use App\Console\Commands\Localization\Datatables\DownloadLocalizations;
use App\Console\Commands\Localization\Datatables\ConvertLocalizations;
use App\Console\Commands\Handlebars\Refresh as HandlebarsRefresh;
use App\Console\Commands\Github\CreateGithubReleaseTicket;
use App\Console\Commands\Github\CreateGithubReleasePullRequest;
use App\Console\Commands\Github\CreateGithubRelease;
use App\Console\Commands\Generate\Repository as GenerateRepository;
use App\Console\Commands\Environment\UpdatePrepare as EnvironmentUpdatePrepare;
use App\Console\Commands\Environment\Update as EnvironmentUpdate;
use App\Console\Commands\Dungeon\ImportInstanceIds;
use App\Console\Commands\Dungeon\CreateMissingFloors;
use App\Console\Commands\Dungeon\CreateMissing;
use App\Console\Commands\Database\SetupMainDatabase;
use App\Console\Commands\Database\SetupCombatLogDatabase;
use App\Console\Commands\Database\SeedOne;
use App\Console\Commands\Database\Backup;
use App\Console\Commands\CombatLogEvent\SaveToOpensearch;
use App\Console\Commands\CombatLog\SplitZoneChange;
use App\Console\Commands\CombatLog\SplitChallengeMode;
use App\Console\Commands\CombatLog\OutputResultEvents;
use App\Console\Commands\CombatLog\OutputCombatLogRouteJson;
use App\Console\Commands\CombatLog\IngestCombatLogRouteJson;
use App\Console\Commands\CombatLog\ExtractUiMapIds;
use App\Console\Commands\CombatLog\ExtractData;
use App\Console\Commands\CombatLog\EnsureChallengeMode;
use App\Console\Commands\CombatLog\DetermineBounds;
use App\Console\Commands\CombatLog\CreateMappingVersion;
use App\Console\Commands\CombatLog\CreateDungeonRoutes;
use App\Console\Commands\ChallengeModeRunData\ConvertToEvents;

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

Artisan::command('inspire', function () {
//    /** @var Command $this */
//    $this->comment(Inspiring::quote());
})->describe('Display an inspiring quote');


try {
    Log::channel('scheduler')->debug('Starting scheduler');

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
    $commands[] = Schedule::command('horizon:snapshot')->everyFiveMinutes();

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
} finally {
    Log::channel('scheduler')->debug('Finished scheduler');
}
