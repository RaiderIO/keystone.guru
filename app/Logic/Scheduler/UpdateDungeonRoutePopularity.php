<?php
/**
 * Created by PhpStorm.
 * User: Wouter
 * Date: 03/12/2018
 * Time: 23:57
 */

namespace App\Logic\Scheduler;

use App\Models\DungeonRoute;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UpdateDungeonRoutePopularity
{
    function __invoke()
    {
        Log::channel('scheduler')->debug('>> Updating dungeonroute popularity');

        DB::update('
            UPDATE dungeon_routes, (
                SELECT model_id, count(0) as views
                FROM page_views
                WHERE page_views.model_class = :modelClass
                AND page_views.created_at > :popularityDate
                GROUP BY page_views.model_id
            ) as page_views
            SET dungeon_routes.popularity = page_views.views
            WHERE dungeon_routes.id = page_views.model_id
        ', [
            'modelClass'     => DungeonRoute::class,
            'popularityDate' => now()->subDays(config('keystoneguru.discover.service.popular_days'))->toDateTimeString()
        ]);

        Log::channel('scheduler')->debug('OK Updating dungeonroute popularity');
    }
}