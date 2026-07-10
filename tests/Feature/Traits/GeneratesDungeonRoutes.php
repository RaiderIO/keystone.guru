<?php

namespace Tests\Feature\Traits;

use App\Logic\MDT\Conversion;
use App\Logic\MDT\Data\MDTDungeon;
use App\Models\Dungeon;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Enemy;
use App\Service\Cache\CacheServiceInterface;
use App\Service\Coordinates\CoordinatesServiceInterface;
use Illuminate\Support\Collection;

trait GeneratesDungeonRoutes
{
    protected function createNonFacadeDungeonRouteWithEnemies(): DungeonRoute
    {
        // Iterate over every challenge-mode dungeon (shuffled for randomness) instead of drawing a
        // single random dungeon a limited number of times. The old approach re-sampled with
        // Dungeon::inRandomOrder()->first() and gave up after 20 tries, so it could repeatedly draw
        // unsuitable dungeons and throw even when a suitable one existed - an intermittent CI flake.
        $dungeons = Dungeon::whereNotNull('challenge_mode_id')->with('floors')->get()->shuffle();

        foreach ($dungeons as $dungeon) {
            /** @var Dungeon $dungeon */
            $mappingVersion = $dungeon->getCurrentMappingVersion();

            if (
                $mappingVersion === null ||
                $mappingVersion->facade_enabled ||
                $dungeon->floors->isEmpty() ||
                $mappingVersion->enemies()->count() === 0
            ) {
                continue;
            }

            return DungeonRoute::factory()->create([
                'dungeon_id'         => $dungeon->id,
                'mapping_version_id' => $mappingVersion->id,
            ]);
        }

        throw new \RuntimeException('Unable to find a non-facade dungeon with enemies');
    }

    /**
     * Returns an MDT-compatible route whose mapping version does not use facades.
     * Facade dungeons convert random factory coordinates through a facade-to-floor
     * projection that can fail for arbitrary lat/lng values, causing intermittent
     * floor-matching failures during import.
     *
     * @param array<string, mixed> $attributes
     */
    protected function getMDTCompatibleNonFacadeDungeonRoute(array $attributes = []): DungeonRoute
    {
        do {
            /** @var DungeonRoute $dungeonRoute */
            $dungeonRoute = DungeonRoute::factory()->make($attributes);

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
     *
     * @param array<string, mixed> $attributes
     */
    protected function getMDTCompatibleDungeonRouteWithSafeEnemies(int $enemyCount = 1, array $attributes = []): DungeonRoute
    {
        do {
            /** @var DungeonRoute $dungeonRoute */
            $dungeonRoute = DungeonRoute::factory()->make($attributes);

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
     * Additionally, applies the same clone-index offset hacks as parseMdtNpcClonesInPull()
     * to exclude enemies that would fail to match during import due to duplicate-NPC merging.
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

        // Lookup: "effectiveNpcId_mdt_id" => MDT clone (used to find mdt_npc_index per enemy)
        $mdtCloneByPair = $mdtClones->keyBy(
            static fn(Enemy $clone): string => sprintf('%d_%d', $clone->npc_id, $clone->mdt_id),
        );

        // Grouped lookup: mdt_npc_index => Collection<Enemy> (used to verify offset clone exists)
        $mdtClonesByNpcIndex = $mdtClones->groupBy('mdt_npc_index');

        $dungeon = $dungeonRoute->dungeon;

        return $dungeonRoute->mappingVersion->enemies()
            ->whereNotNull('mdt_id')
            ->where(fn($q) => $q->where('teeming', '!=', Enemy::TEEMING_VISIBLE)->orWhereNull('teeming'))
            ->where(fn($q) => $q->where('seasonal_type', '!=', Enemy::SEASONAL_TYPE_MDT_PLACEHOLDER)->orWhereNull('seasonal_type'))
            ->whereNull('seasonal_index')
            ->get()
            ->filter(static function (Enemy $enemy) use ($mdtCloneByPair, $mdtClonesByNpcIndex, $dungeon): bool {
                $effectiveNpcId = $enemy->mdt_npc_id ?? $enemy->npc_id;
                $mdtClone       = $mdtCloneByPair->get(sprintf('%d_%d', $effectiveNpcId, $enemy->mdt_id));

                if ($mdtClone === null) {
                    return false;
                }

                $npcIndex    = $mdtClone->mdt_npc_index;
                $importMdtId = $enemy->mdt_id;

                // Mirror the clone-index offset hacks from MDTImportStringService::parseMdtNpcClonesInPull().
                // These compensate for MDT's duplicate-NPC merging, which shifts clone indices for
                // specific NPCs. Without this check, enemies near the top of the accessible clone
                // range would pass the MDT-existence filter but fail during import.
                if ($dungeon->key === Dungeon::DUNGEON_SIEGE_OF_BORALUS && $npcIndex === 35) {
                    $importMdtId += 15;
                } elseif ($dungeon->key === Dungeon::DUNGEON_TOL_DAGOR && $npcIndex === 11) {
                    $importMdtId += 2;
                } elseif ($dungeon->key === Dungeon::DUNGEON_MISTS_OF_TIRNA_SCITHE && $npcIndex === 23) {
                    $importMdtId += 5;
                }

                if ($importMdtId === $enemy->mdt_id) {
                    return true;
                }

                return $mdtClonesByNpcIndex->has($npcIndex) &&
                    $mdtClonesByNpcIndex->get($npcIndex)->contains('mdt_id', $importMdtId);
            })
            ->shuffle()
            ->take($limit)
            ->values();
    }
}
