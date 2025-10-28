<?php

namespace App\Logic\MapContext\Traits;

use App\Models\AffixGroup\AffixGroup;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonRoute\DungeonRouteEnemyRaidMarker;
use App\Models\User;
use App\Service\Coordinates\CoordinatesServiceInterface;
use Illuminate\Support\Collection;

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
