<?php

namespace Tests\Feature\Traits;

use App\Logic\MDT\Conversion;
use App\Models\DungeonRoute\DungeonRoute;

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
}
