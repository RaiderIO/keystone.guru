<?php

namespace Tests\Feature\Traits;

use App\Logic\MDT\Conversion;
use App\Logic\MDT\Data\MDTDungeon;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Enemy;
use App\Service\Cache\CacheServiceInterface;
use App\Service\Coordinates\CoordinatesServiceInterface;
use Illuminate\Support\Collection;

trait GeneratesDungeonRoutes {
    /**
     * Returns an MDT-compatible route whose mapping version does not use facades.
     * Facade dungeons convert random factory coordinates through a facade-to-floor
     * projection that can fail for arbitrary lat/lng values, causing intermittent
     * floor-matching failures during import.
     */
    protected function getMDTCompatibleNonFacadeDungeonRoute(array $attributes = []): DungeonRoute
    {
        do {
            /** @var DungeonRoute $dungeonRoute */
            $dungeonRoute = DungeonRoute::factory()->make(array_merge([
                'expires_at' => now()->addHour(),
            ], $attributes));

            $dungeonRoute->load(['dungeon', 'mappingVersion']);

            if (
                !Conversion::hasMDTDungeonName($dungeonRoute->dungeon->key) ||
                $dungeonRoute->mappingVersion->facade_enabled
            ) {
                $dungeonRoute = null;
            }
        } while ($dungeonRoute === null);

        $dungeonRoute->save();

        return $dungeonRoute;
    }


    /**
     * Returns an MDT-compatible route that has at least $enemyCount enemies guaranteed to
     * survive an import round-trip. Filters out teeming-only enemies, MDT placeholders,
     * and seasonally restricted enemies that the import service would skip.
     */
    protected function getMDTCompatibleDungeonRouteWithSafeEnemies(int $enemyCount = 1, array $attributes = []): DungeonRoute
    {
        do {
            /** @var DungeonRoute $dungeonRoute */
            $dungeonRoute = DungeonRoute::factory()->make(array_merge([
                'expires_at' => now()->addHour(),
            ], $attributes));

            $dungeonRoute->load(['dungeon', 'mappingVersion']);

            if (
                !Conversion::hasMDTDungeonName($dungeonRoute->dungeon->key) ||
                $dungeonRoute->mappingVersion->facade_enabled ||
                $this->getSafeMdtEnemies($dungeonRoute, $enemyCount)->count() < $enemyCount
            ) {
                $dungeonRoute = null;
            }
        } while ($dungeonRoute === null);

        $dungeonRoute->save();

        return $dungeonRoute;
    }

    /**
     * Returns enemies that are guaranteed to survive an import round-trip.
     * Filters out teeming-only enemies, MDT placeholders, and seasonally-restricted
     * enemies that would be skipped by the import service based on route conditions.
     * Also cross-checks against the actual MDT clone data to exclude enemies whose
     * mdt_id does not exist in the MDT Lua file (e.g. KG has mdt_id=1 but MDT starts at 2).
     *
     * @return Collection<int, Enemy>
     */
    protected function getSafeMdtEnemies(DungeonRoute $dungeonRoute, int $limit = 1): Collection
    {
        $mdtClones = app(MDTDungeon::class, [
            'cacheService'       => app(CacheServiceInterface::class),
            'coordinatesService' => app(CoordinatesServiceInterface::class),
            'dungeon'            => $dungeonRoute->dungeon,
        ])->getClonesAsEnemies($dungeonRoute->mappingVersion, $dungeonRoute->dungeon->floors);

        // Build a lookup of valid (effectiveNpcId_mdtId) pairs from the actual MDT data
        $validMdtPairs = $mdtClones
            ->map(static fn(Enemy $clone) => sprintf('%d_%d', $clone->npc_id, $clone->mdt_id))
            ->flip();

        return $dungeonRoute->mappingVersion->enemies()
            ->whereNotNull('mdt_id')
            ->where(fn($q) => $q->where('teeming', '!=', Enemy::TEEMING_VISIBLE)->orWhereNull('teeming'))
            ->where(fn($q) => $q->where('seasonal_type', '!=', Enemy::SEASONAL_TYPE_MDT_PLACEHOLDER)->orWhereNull('seasonal_type'))
            ->whereNull('seasonal_index')
            ->get()
            ->filter(static function (Enemy $enemy) use ($validMdtPairs): bool {
                return $validMdtPairs->has(sprintf('%d_%d', $enemy->mdt_npc_id ?? $enemy->npc_id, $enemy->mdt_id));
            })
            ->shuffle()
            ->take($limit)
            ->values();
    }

}
