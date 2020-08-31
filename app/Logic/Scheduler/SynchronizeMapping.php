<?php
/**
 * Created by PhpStorm.
 * User: Wouter
 * Date: 29/08/2020
 * Time: 17:06
 */

namespace App\Logic\Scheduler;

use App\Models\Mapping\MappingChangeLog;
use App\Models\Mapping\MappingCommitLog;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class SynchronizeMapping
{
    use ChecksForDuplicateJobs;

    function __invoke()
    {
        Log::channel('scheduler')->debug('>> Synchronizing mapping');

        /** @var MappingChangeLog $mostRecentMappingChangeLog */
        $mostRecentMappingChangeLog = MappingChangeLog::latest()->first();

        /** @var MappingCommitLog $mostRecentMappingCommitLog */
        $mostRecentMappingCommitLog = MappingCommitLog::latest()->first();

        if ($mostRecentMappingChangeLog !== null) {
            // If not synced at all yet, or if we've synced, but it was before any changes were done
            if ($mostRecentMappingCommitLog === null || $mostRecentMappingChangeLog->shouldSynchronize($mostRecentMappingCommitLog)) {
                if (Artisan::call('mapping:save') === 0 &&
                    Artisan::call('mapping:commit') === 0 &&
                    Artisan::call('mapping:merge') === 0) {
                    Log::channel('scheduler')->debug('Successfully synced mapping with Github!');
                }
            }
        }


        Log::channel('scheduler')->debug('OK Synchronizing mapping');
    }
}