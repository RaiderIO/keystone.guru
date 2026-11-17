<?php

namespace App\Repositories\Database\KillZone;

use App\Models\KillZone\KillZoneEnemy;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Interfaces\KillZone\KillZoneEnemyRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class KillZoneEnemyRepository extends DatabaseRepository implements KillZoneEnemyRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(KillZoneEnemy::class);
    }

    /**
     * @param Collection<int, int> $killZoneIds
     */
    public function resetEnemyIdByKillZoneIds(Collection $killZoneIds): void
    {
        KillZoneEnemy::whereIn('kill_zone_id', $killZoneIds)->update(['enemy_id' => null]);
    }

    public function updateEnemyIdsByMappingVersion(int $dungeonRouteId, int $mappingVersionId): void
    {
        DB::statement('
            UPDATE kill_zone_enemies kze
            JOIN kill_zones kz ON kz.id = kze.kill_zone_id
            JOIN enemies e
                ON kze.npc_id = COALESCE(e.mdt_npc_id, e.npc_id)
                AND kze.mdt_id = e.mdt_id
                AND e.mapping_version_id = ?
            SET kze.enemy_id = e.id
            WHERE kz.dungeon_route_id = ?
        ', [$mappingVersionId, $dungeonRouteId]);
    }

    /**
     * @param Collection<int, int> $killZoneIds
     */
    public function deleteOrphanedByKillZoneIds(Collection $killZoneIds): void
    {
        KillZoneEnemy::whereIn('kill_zone_id', $killZoneIds)->whereNull('enemy_id')->delete();
    }

    /**
     * @param  array<int, int>                  $dungeonRouteIds
     * @param  array<int, int>                  $npcIds
     * @return Collection<int, array<int, int>>
     */
    public function getNpcIdsInPullsPerDungeonRoute(array $dungeonRouteIds, array $npcIds): Collection
    {
        if ($dungeonRouteIds === [] || $npcIds === []) {
            return collect();
        }

        // kill_zone_enemies.npc_id holds the MDT npc_id when the enemy overrides it, so resolve the mapped one
        $resolvedNpcId = DB::raw('COALESCE(enemies.npc_id, kill_zone_enemies.npc_id)');

        return KillZoneEnemy::query()
            ->join('kill_zones', 'kill_zones.id', '=', 'kill_zone_enemies.kill_zone_id')
            ->leftJoin('enemies', 'enemies.id', '=', 'kill_zone_enemies.enemy_id')
            ->whereIn('kill_zones.dungeon_route_id', $dungeonRouteIds)
            ->whereIn($resolvedNpcId, $npcIds)
            ->distinct()
            ->select(['kill_zones.dungeon_route_id', DB::raw('COALESCE(enemies.npc_id, kill_zone_enemies.npc_id) AS npc_id')])
            ->get()
            ->groupBy('dungeon_route_id')
            ->map(static fn(Collection $killZoneEnemies): array => $killZoneEnemies->pluck('npc_id')->map(static fn($npcId): int => (int)$npcId)->values()->all());
    }
}
