<?php

namespace App\Mcp\Tools\Route\Concerns;

use App\Mcp\Exceptions\McpToolException;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Enemy;
use App\Repositories\Interfaces\DungeonRoute\DungeonRouteRepositoryInterface;
use App\Repositories\Interfaces\KillZone\KillZoneEnemyRepositoryInterface;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

/**
 * Turns pack and enemy ids into the enemies of the route's mapping version a pull kills, refusing ids of any other
 * mapping, enemies the route editor hides on the route and enemies another pull of the route already kills.
 *
 * @property-read DungeonRouteRepositoryInterface  $dungeonRouteRepository
 * @property-read KillZoneEnemyRepositoryInterface $killZoneEnemyRepository
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
        $pullableEnemies = $this->getPullableEnemies($dungeonRoute);

        // A pack holding no pullable enemy is as unknown to the client as get_dungeon_packs leaves it
        $knownPackIds = $pullableEnemies->pluck('enemy_pack_id')
            ->filter(static fn($id) => $id !== null && in_array((int)$id, $packIds, true))
            ->map(static fn($id) => (int)$id)
            ->unique()
            ->values()
            ->all();
        $unknownPackIds = array_diff($packIds, $knownPackIds);
        if ($unknownPackIds !== []) {
            throw new McpToolException(__('mcp.error.unknown_pack_ids', ['ids' => implode(', ', $unknownPackIds)]));
        }

        $knownEnemyIds   = array_values(array_filter($enemyIds, static fn(int $id) => $pullableEnemies->has($id)));
        $unknownEnemyIds = array_diff($enemyIds, $knownEnemyIds);
        if ($unknownEnemyIds !== []) {
            throw new McpToolException(__('mcp.error.unknown_enemy_ids', ['ids' => implode(', ', $unknownEnemyIds)]));
        }

        $packEnemyIds = $pullableEnemies
            ->filter(static fn(Enemy $enemy) => $enemy->enemy_pack_id !== null && in_array((int)$enemy->enemy_pack_id, $knownPackIds, true))
            ->keys()
            ->map(static fn($id) => (int)$id)
            ->all();

        $result = array_values(array_unique(array_merge($packEnemyIds, $knownEnemyIds)));
        sort($result);

        return $result;
    }

    /**
     * The enemies of the route's mapping version a pull can kill: every enemy the route editor shows on the route.
     *
     * @return EloquentCollection<int, Enemy> enemy id => enemy
     */
    private function getPullableEnemies(DungeonRoute $dungeonRoute): EloquentCollection
    {
        $dungeonRoute->loadMissing('affixes.affixes');

        $enemies = $dungeonRoute->mappingVersion->enemies()->with('npc')->get();

        /** @var array<int, true> $finalBossPackIds */
        $finalBossPackIds = $enemies
            ->filter(static fn(Enemy $enemy) => $enemy->enemy_pack_id !== null && $enemy->npc?->isFinalBoss())
            ->mapWithKeys(static fn(Enemy $enemy) => [$enemy->enemy_pack_id => true])
            ->all();
        $isLinkedToLastBoss = static fn(Enemy $enemy) => $enemy->enemy_pack_id !== null && isset($finalBossPackIds[$enemy->enemy_pack_id]);

        $awakenedLinkedToLastBossIds = $enemies
            ->filter(static fn(Enemy $enemy) => $enemy->npc?->isAwakened() && $isLinkedToLastBoss($enemy))
            ->pluck('id')
            ->all();
        /** @var array<int, int> $pulledEnemyIds enemy id => enemy id */
        $pulledEnemyIds = $awakenedLinkedToLastBossIds === [] ? [] : $this->killZoneEnemyRepository
            ->getPullIndicesOfEnemies($dungeonRoute, $awakenedLinkedToLastBossIds)
            ->mapWithKeys(static fn($row) => [(int)$row->enemy_id => (int)$row->enemy_id])
            ->all();

        return $enemies
            ->reject(static fn(Enemy $enemy) => $enemy->isIgnoredOnDungeonRoute($dungeonRoute, $isLinkedToLastBoss($enemy), isset($pulledEnemyIds[$enemy->id])))
            ->keyBy('id');
    }

    /**
     * Serializes pull edits per route: two concurrent calls would otherwise both pass ensureNotPulledYet() and pull the
     * same enemy twice. Call it first inside the transaction that checks and saves.
     */
    protected function lockDungeonRoute(DungeonRoute $dungeonRoute): void
    {
        $this->dungeonRouteRepository->lockForUpdate($dungeonRoute);
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

        $alreadyPulled = $this->killZoneEnemyRepository->getPullIndicesOfEnemies($dungeonRoute, $enemyIds, $exceptKillZoneId);

        if ($alreadyPulled->isNotEmpty()) {
            throw new McpToolException(__('mcp.error.enemies_already_pulled', [
                'enemies' => $alreadyPulled->map(static fn($row) => sprintf('%d => %d', $row->enemy_id, $row->index))->implode(', '),
            ]));
        }
    }
}
