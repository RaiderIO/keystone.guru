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


}
