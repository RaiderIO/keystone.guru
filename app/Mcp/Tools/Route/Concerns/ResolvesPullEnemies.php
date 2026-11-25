<?php

namespace App\Mcp\Tools\Route\Concerns;

use App\Mcp\Exceptions\McpToolException;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\KillZone\KillZoneEnemy;

/**
 * Turns pack and enemy ids into the enemies of the route's mapping version a pull kills, refusing ids of any other
 * mapping and enemies another pull of the route already kills.
 */
trait ResolvesPullEnemies
{
    /**
     * @param  array<int, int> $packIds
     * @param  array<int, int> $enemyIds
     * @return list<int>       sorted, unique enemy ids
     *
     * @throws McpToolException
     */
    protected function getEnemyIdsOfPacksAndEnemies(DungeonRoute $dungeonRoute, array $packIds, array $enemyIds): array
    {
        $mappingVersion = $dungeonRoute->mappingVersion;

        $knownPackIds   = $mappingVersion->enemyPacks()->whereIn('id', $packIds)->pluck('id')->map(static fn($id) => (int)$id)->all();
        $unknownPackIds = array_diff($packIds, $knownPackIds);
        if ($unknownPackIds !== []) {
            throw new McpToolException(__('mcp.error.unknown_pack_ids', ['ids' => implode(', ', $unknownPackIds)]));
        }

        $knownEnemyIds   = $mappingVersion->enemies()->whereIn('id', $enemyIds)->pluck('id')->map(static fn($id) => (int)$id)->all();
        $unknownEnemyIds = array_diff($enemyIds, $knownEnemyIds);
        if ($unknownEnemyIds !== []) {
            throw new McpToolException(__('mcp.error.unknown_enemy_ids', ['ids' => implode(', ', $unknownEnemyIds)]));
        }

        $packEnemyIds = $knownPackIds === [] ? [] : $mappingVersion->enemies()
            ->whereIn('enemy_pack_id', $knownPackIds)
            ->pluck('id')
            ->map(static fn($id) => (int)$id)
            ->all();

        $result = array_values(array_unique(array_merge($packEnemyIds, $knownEnemyIds)));
        sort($result);

        return $result;
    }

    /**
     * Serializes pull edits per route: two concurrent calls would otherwise both pass ensureNotPulledYet() and pull the
     * same enemy twice. Call it first inside the transaction that checks and saves.
     */
    protected function lockDungeonRoute(DungeonRoute $dungeonRoute): void
    {
        DungeonRoute::query()->whereKey($dungeonRoute->id)->lockForUpdate()->first();
    }

    /**
     * @param list<int> $enemyIds
     *
     * @throws McpToolException
     */
    protected function ensureNotPulledYet(DungeonRoute $dungeonRoute, array $enemyIds, ?int $exceptKillZoneId = null): void
    {
        if ($enemyIds === []) {
            return;
        }

        $alreadyPulled = KillZoneEnemy::query()
            ->join('kill_zones', 'kill_zones.id', '=', 'kill_zone_enemies.kill_zone_id')
            ->where('kill_zones.dungeon_route_id', $dungeonRoute->id)
            ->when($exceptKillZoneId !== null, static fn($query) => $query->where('kill_zones.id', '<>', $exceptKillZoneId))
            ->whereIn('kill_zone_enemies.enemy_id', $enemyIds)
            ->orderBy('kill_zone_enemies.enemy_id')
            ->toBase()
            ->get(['kill_zone_enemies.enemy_id', 'kill_zones.index']);

        if ($alreadyPulled->isNotEmpty()) {
            throw new McpToolException(__('mcp.error.enemies_already_pulled', [
                'enemies' => $alreadyPulled->map(static fn($row) => sprintf('%d => %d', $row->enemy_id, $row->index))->implode(', '),
            ]));
        }
    }
}
