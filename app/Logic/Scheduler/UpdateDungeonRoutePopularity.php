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
            ) as page_views,
            (
                SELECT MAX(id) as ids
                FROM mapping_versions
                GROUP BY mapping_versions.dungeon_id
            ) as latest_mapping_version_ids
            SET dungeon_routes.popularity = page_views.views
            /*
                This will calculate a number between 1 and 0 depending on the age of the route. A new route will generate 1.
                A route at popularityFalloffDays days will produce 0. This will ensure that old routes fall off the
                popularity board over time and the overview stays fresh
            */
                * GREATEST(0, (1 - DATEDIFF(NOW(), dungeon_routes.updated_at) / :popularityFalloffDays))
            /*
                Adds a penalty if your route does not use the latest mapping version for your dungeon
             */
                * IF(FIND_IN_SET(dungeon_routes.mapping_version_id, latest_mapping_version_ids.ids) > 1, 1, :outOfDateMappingVersionPenalty)
            WHERE dungeon_routes.id = page_views.model_id
        ', [
            'modelClass'                     => DungeonRoute::class,
            'popularityDate'                 => now()->subDays(config('keystoneguru.discover.service.popular_days'))->toDateTimeString(),
            'popularityFalloffDays'          => config('keystoneguru.discover.service.popular_falloff_days'),
            'outOfDateMappingVersionPenalty' => config('keystoneguru.discover.service.popular_out_of_date_mapping_version_penalty'),
        ]);

        Log::channel('scheduler')->debug('OK Updating dungeonroute popularity');
    }
}
