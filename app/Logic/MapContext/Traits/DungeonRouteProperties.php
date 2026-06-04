<?php

namespace App\Logic\MapContext\Traits;

/**
 * Trait DungeonRouteTrait
 */
trait DungeonRouteProperties
{
//    /**
//     * @return Collection<DungeonRoute>
//     */
//    private function getDungeonRoutesProperties(
//        CoordinatesServiceInterface $coordinatesService,
//        array                       $publicKeys,
//    ): Collection {
//        $result = collect();
//
//        /** @var Collection<DungeonRoute> $dungeonRoutes */
//        $dungeonRoutes = DungeonRoute::with([
//            'killZones',
//            'mapicons',
//            'paths',
//            'brushlines',
//            'pridefulEnemies',
//            'enemyRaidMarkers',
//        ])->whereIn('public_key', $publicKeys)->get();
//
//        foreach ($dungeonRoutes as $dungeonRoute) {
//            $result->put($dungeonRoute->public_key, $this->getDungeonRouteProperties($coordinatesService, $dungeonRoute));
//        }
//
//        return $result;
//    }
}
