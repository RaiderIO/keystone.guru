<?php

namespace App\Repositories\Database\DungeonRoute;

use App\Models\DungeonRoute\DungeonRouteEnemyRaidMarker;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Interfaces\DungeonRoute\DungeonRouteEnemyRaidMarkerRepositoryInterface;
use Illuminate\Support\Facades\DB;

class DungeonRouteEnemyRaidMarkerRepository extends DatabaseRepository implements DungeonRouteEnemyRaidMarkerRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(DungeonRouteEnemyRaidMarker::class);
    }

    public function resetEnemyIdByDungeonRouteId(int $dungeonRouteId): void
    {
        DungeonRouteEnemyRaidMarker::where('dungeon_route_id', $dungeonRouteId)->update(['enemy_id' => null]);
    }

    public function updateEnemyIdsByMappingVersion(int $dungeonRouteId, int $mappingVersionId): void
    {
        DB::statement('
            UPDATE dungeon_route_enemy_raid_markers drerm
            JOIN enemies e
                ON drerm.npc_id = COALESCE(e.mdt_npc_id, e.npc_id)
                AND drerm.mdt_id = e.mdt_id
                AND e.mapping_version_id = ?
            SET drerm.enemy_id = e.id
            WHERE drerm.dungeon_route_id = ?
        ', [$mappingVersionId, $dungeonRouteId]);
    }

    public function deleteOrphanedByDungeonRouteId(int $dungeonRouteId): void
    {
        DungeonRouteEnemyRaidMarker::where('dungeon_route_id', $dungeonRouteId)->whereNull('enemy_id')->delete();
    }
}
