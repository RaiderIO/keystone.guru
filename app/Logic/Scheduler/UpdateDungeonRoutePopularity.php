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
            /*
            This will calculate a number between 1 and 0 depending on the age of the route. A new route will generate 1. A route at popularityFalloffDays days will produce 0
            This will ensure that old routes fall off the popularity board over time and the overview stays fresh
            */
            SET dungeon_routes.popularity = page_views.views * GREATEST(0, (1 - DATEDIFF(NOW(), dungeon_routes.created_at) / :popularityFalloffDays))
            WHERE dungeon_routes.id = page_views.model_id
        ', [
            'modelClass'            => DungeonRoute::class,
            'popularityDate'        => now()->subDays(config('keystoneguru.discover.service.popular_days'))->toDateTimeString(),
            'popularityFalloffDays' => config('keystoneguru.discover.service.popular_falloff_days'),
        ]);

        Log::channel('scheduler')->debug('OK Updating dungeonroute popularity');
    }
}
