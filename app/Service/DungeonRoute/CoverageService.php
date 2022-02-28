<?php


namespace App\Service\DungeonRoute;

use App\Models\DungeonRoute;
use App\Service\Expansion\ExpansionServiceInterface;
use App\User;
use Illuminate\Support\Collection;

class CoverageService implements CoverageServiceInterface
{
    /** @var ExpansionServiceInterface */
    private ExpansionServiceInterface $expansionService;

    /**
     * @param ExpansionServiceInterface $expansionService
     */
    public function __construct(ExpansionServiceInterface $expansionService)
    {
        $this->expansionService = $expansionService;
    }

    /**
     * @inheritDoc
     */
    function getForUser(User $user): Collection
    {
        $currentExpansion = $this->expansionService->getCurrentExpansion();

        return DungeonRoute::with(['affixes'])
            ->selectRaw('dungeon_routes.*, IF(dungeons.enemy_forces_required > dungeon_routes.enemy_forces, 0, 1) as has_enemy_forces')
            ->join('dungeons', 'dungeons.id', 'dungeon_routes.dungeon_id')
            ->join('dungeon_route_affix_groups', 'dungeon_route_affix_groups.dungeon_route_id', 'dungeon_routes.id')
            ->join('affix_groups', 'affix_groups.id', 'dungeon_route_affix_groups.affix_group_id')
            ->where('dungeon_routes.author_id', $user->id)
            ->where('dungeons.expansion_id', $currentExpansion->id)
            ->where('affix_groups.season_id', $currentExpansion->currentseason->id)
            ->whereNull('expires_at')
            ->groupBy('dungeon_routes.id')
            ->get()
            ->groupBy('dungeon_id');

//            SELECT dungeon_routes.*, IF(dungeons.enemy_forces_required > dungeon_routes.enemy_forces, 0, 1) as has_enemy_forces
//            FROM dungeon_routes
//            INNER JOIN dungeons ON dungeons.id = dungeon_routes.dungeon_id
//            INNER JOIN dungeon_route_affix_groups ON dungeon_route_affix_groups.dungeon_route_id = dungeon_routes.id
//            INNER JOIN affix_groups ON affix_groups.id = dungeon_route_affix_groups.affix_group_id
//            WHERE dungeon_routes.author_id = 1
//            AND dungeons.expansion_id = 3
//            AND affix_groups.season_id = 6;
//            GROUP BY dungeon_routes.id
    }

}
