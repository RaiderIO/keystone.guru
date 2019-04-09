<?php
/**
 * Created by PhpStorm.
 * User: Wouter
 * Date: 09/04/2019
 * Time: 18:00
 */

namespace App\Logic\Scheduler;

use App\Models\DungeonRoute;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Log;

class DeleteExpiredDungeonRoutes
{
    use ChecksForDuplicateJobs;

    function __invoke()
    {
        Log::channel('scheduler')->debug('>> Deleting expired routes');

        /** @var Builder $query */
        $dungeonRoutes = \App\Models\DungeonRoute::whereDate('expires_at', '<', \Illuminate\Support\Carbon::now()->toDateTimeString())
            ->where('expires_at', '!=', 0)->whereNotNull('expires_at')->get();

        // Retrieve all routes and then delete them
        foreach ($dungeonRoutes as $dungeonRoute) {
            /** @var $dungeonRoute DungeonRoute */
            try {
                $dungeonRoute->delete();
            } catch (\Exception $ex) {
                Log::channel('scheduler')->error($ex);
            }
        }

        Log::channel('scheduler')->debug(sprintf('Deleted %s routes because they expired (from trying)', $nrDeleted));
        Log::channel('scheduler')->debug('OK Deleting expired routes');
    }
}