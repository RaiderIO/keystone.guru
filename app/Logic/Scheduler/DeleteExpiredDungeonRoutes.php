<?php
/**
 * Created by PhpStorm.
 * User: Wouter
 * Date: 09/04/2019
 * Time: 18:00
 */

namespace App\Logic\Scheduler;

use App\Models\DungeonRoute;
use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class DeleteExpiredDungeonRoutes
{
    use ChecksForDuplicateJobs;

    function __invoke()
    {
        Log::channel('scheduler')->debug('>> Deleting expired routes');

        /** @var Collection $dungeonRoutes */
        $dungeonRoutes = DungeonRoute::whereDate('expires_at', '<', Carbon::now()->toDateTimeString())
            ->where('expires_at', '!=', 0)->whereNotNull('expires_at')->get();

        // Retrieve all routes and then delete them
        foreach ($dungeonRoutes as $dungeonRoute) {
            /** @var $dungeonRoute DungeonRoute */
            try {
                $dungeonRoute->delete();
            } catch (Exception $ex) {
                Log::channel('scheduler')->error($ex);
            }
        }

        Log::channel('scheduler')->debug(sprintf('Deleted %s routes because they expired (from sandbox functionality)', $dungeonRoutes->count()));
        Log::channel('scheduler')->debug('OK Deleting expired routes');
    }
}