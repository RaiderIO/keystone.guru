<?php


namespace App\Service\DungeonRoute;

use App\Models\DungeonRoute;
use App\Models\Season;
use App\User;
use Illuminate\Support\Collection;

class CoverageService implements CoverageServiceInterface
{
    /**
     * @inheritDoc
     */
    function getForUser(User $user, Season $season): Collection
    {
        return DungeonRoute::with(['affixes'])
            ->selectRaw('dungeon_routes.*, IF(dungeons.enemy_forces_required > dungeon_routes.enemy_forces, 0, 1) as has_enemy_forces')
            ->join('dungeons', 'dungeons.id', 'dungeon_routes.dungeon_id')
            ->join('dungeon_route_affix_groups', 'dungeon_route_affix_groups.dungeon_route_id', 'dungeon_routes.id')
            ->join('affix_groups', 'affix_groups.id', 'dungeon_route_affix_groups.affix_group_id')
            ->join('season_dungeons', 'season_dungeons.dungeon_id', 'dungeons.id')
            ->where('dungeon_routes.author_id', $user->id)
            ->where('affix_groups.season_id', $season->id)
            ->where('season_dungeons.season_id', $season->id)
            ->whereNull('expires_at')
            ->groupBy('dungeon_routes.id')
            ->get()
            ->groupBy('dungeon_id');

//            select dungeon_routes.*, IF(dungeons.enemy_forces_required > dungeon_routes.enemy_forces, 0, 1) as has_enemy_forces
//            from `dungeon_routes`
//                     inner join `dungeons` on `dungeons`.`id` = `dungeon_routes`.`dungeon_id`
//                     inner join `dungeon_route_affix_groups`
//                                on `dungeon_route_affix_groups`.`dungeon_route_id` = `dungeon_routes`.`id`
//                     inner join `affix_groups` on `affix_groups`.`id` = `dungeon_route_affix_groups`.`affix_group_id`
//                     inner join `season_dungeons` on `season_dungeons`.`dungeon_id` = `dungeons`.`id`
//            where `dungeon_routes`.`author_id` = 1
//              and `affix_groups`.`season_id` = 9
//              and `season_dungeons`.`season_id` = 9
//              and `expires_at` is null
//            group by `dungeon_routes`.`id`
    }

}
