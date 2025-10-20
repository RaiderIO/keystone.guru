<?php

namespace App\Http\Controllers\Javascript;

use App\Models\Dungeon;
use App\Models\Mapping\MappingVersion;
use App\Service\Cache\CacheServiceInterface;
use App\Service\Cache\Traits\RemembersToFile;
use App\Service\MapContext\MapContextServiceInterface;

class JavascriptController
{
    use RemembersToFile;

    public function mapContextDungeonRoute(
        string                     $version,
        Dungeon                    $dungeon,
        MappingVersion             $mappingVersion,
        string                     $facadeStyle,
        MapContextServiceInterface $mapContextService,
        CacheServiceInterface      $cacheService,
    ) {
//        return $this->rememberLocal(
//            sprintf('ks_mapcontext_dungeonroute_%s_%d_%d_%s', $version, $dungeon->id, $mappingVersion->id, $facadeStyle),
//            86400,
//            function () use (
//                $dungeon,
//                $mappingVersion,
//                $facadeStyle,
//                $mapContextService,
//                $cacheService
//            ) {
//                return $mapContextService->createMapContextDungeonRoute(
//                    $dungeon,
//                    $mappingVersion,
//                    $facadeStyle,
//                );
//            }
//        );

        return view('common.maps.context');
    }
}
