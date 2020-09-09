<?php
/**
 * Created by PhpStorm.
 * User: Wouter
 * Date: 29/08/2020
 * Time: 17:06
 */

namespace App\Logic\Scheduler;

use App\Service\Mapping\MappingService;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class SynchronizeMapping
{
    function __invoke()
    {
        Log::channel('scheduler')->debug('>> Synchronizing mapping');

        /** @var MappingService $mappingService */
        $mappingService = App::make(MappingService::class);

        if ($mappingService->shouldSynchronizeMapping()) {
            if (Artisan::call('mapping:save') === 0 &&
                Artisan::call('mapping:commit') === 0 &&
                Artisan::call('mapping:merge') === 0) {
                Log::channel('scheduler')->debug('Successfully synchronized mapping with Github!');
            }
        }

        Log::channel('scheduler')->debug('OK Synchronizing mapping');
    }
}