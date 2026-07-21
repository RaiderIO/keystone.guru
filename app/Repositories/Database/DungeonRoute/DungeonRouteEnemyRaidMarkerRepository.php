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
        // Null-safe equality (<=>) for mdt_id: enemies placed outside of an MDT import
        // legitimately have a null mdt_id, and plain `=` never matches NULL against NULL.
        //
        // The npc_id/mdt_id identity is NOT guaranteed unique within a mapping version - a small
        // number of NPCs are placed twice under the same MDT clone index. Without the uniqueness
        // guard below, the JOIN would match multiple enemies for one marker row and MySQL would
        // pick an arbitrary one, silently re-binding (or losing) a marker to the wrong enemy.
        // Leaving enemy_id unresolved (still NULL from resetEnemyIdByDungeonRouteId) for an
        // ambiguous identity is safe: deleteOrphanedByDungeonRouteId() then drops the marker
        // rather than guessing, the same outcome as if its enemy no longer existed at all.
        DB::statement('
            UPDATE dungeon_route_enemy_raid_markers drerm
            JOIN enemies e
                ON drerm.npc_id = COALESCE(e.mdt_npc_id, e.npc_id)
                AND drerm.mdt_id <=> e.mdt_id
                AND e.mapping_version_id = ?
            SET drerm.enemy_id = e.id
            WHERE drerm.dungeon_route_id = ?
              AND (
                  SELECT COUNT(*)
                  FROM enemies e2
                  WHERE e2.mapping_version_id = ?
                    AND drerm.npc_id = COALESCE(e2.mdt_npc_id, e2.npc_id)
                    AND drerm.mdt_id <=> e2.mdt_id
              ) = 1
        ', [$mappingVersionId, $dungeonRouteId, $mappingVersionId]);
    }

    public function deleteOrphanedByDungeonRouteId(int $dungeonRouteId): void
    {
        DungeonRouteEnemyRaidMarker::where('dungeon_route_id', $dungeonRouteId)->whereNull('enemy_id')->delete();
    }
}
