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

class UpdateDungeonRouteRating
{
    function __invoke()
    {
        Log::channel('scheduler')->debug('>> Updating dungeonroute rating');

        DB::update('
            UPDATE dungeon_routes, (
                SELECT dungeon_route_id, avg(dungeon_route_ratings.rating) as ratingAvg, count(dungeon_route_ratings.rating) as ratingCount
                            FROM dungeon_route_ratings
                            GROUP BY dungeon_route_ratings.dungeon_route_id
                ) as ratings
            SET dungeon_routes.rating = ratings.ratingAvg, dungeon_routes.rating_count = ratings.ratingCount
            WHERE dungeon_routes.id = ratings.dungeon_route_id
        ');

        Log::channel('scheduler')->debug('OK Updating dungeonroute rating');
    }
}
