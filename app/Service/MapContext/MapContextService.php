<?php

namespace App\Service\MapContext;

use App\Logic\MapContext\Map\MapContextDungeonExplore;
use App\Logic\MapContext\Map\MapContextDungeonRoute;
use App\Logic\MapContext\Map\MapContextLiveSession;
use App\Logic\MapContext\Map\MapContextMappingVersionEdit;
use App\Logic\MapContext\MapContextDungeonData;
use App\Logic\MapContext\MapContextStaticData;
use App\Models\Dungeon;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\LiveSession;
use App\Models\Mapping\MappingVersion;
use App\Service\Cache\CacheServiceInterface;
use App\Service\Coordinates\CoordinatesServiceInterface;
use App\Service\LiveSession\OverpulledEnemyServiceInterface;
use App\Service\Season\SeasonServiceInterface;

/**
 * @TODO Add caching layer here, instead of in the map context classes?
 */
class MapContextService implements MapContextServiceInterface
{
    public function __construct(
        private readonly CacheServiceInterface           $cacheService,
        private readonly CoordinatesServiceInterface     $coordinatesService,
        private readonly OverpulledEnemyServiceInterface $overpulledEnemyService,
        private readonly SeasonServiceInterface          $seasonService,
    ) {
    }

    public function createMapContextDungeonData(
        Dungeon        $dungeon,
        MappingVersion $mappingVersion,
        string         $mapFacadeStyle,
    ): MapContextDungeonData {
        return new MapContextDungeonData(
            $this->cacheService,
            $this->coordinatesService,
            $dungeon,
            $mappingVersion,
            $mapFacadeStyle,
        );
    }

    public function createMapContextStaticData(
        string $locale,
    ): MapContextStaticData {
        return new MapContextStaticData(
            $this->cacheService,
            $locale,
        );
    }

    public function createMapContextDungeonRoute(
        DungeonRoute $dungeonRoute,
        string       $mapFacadeStyle,
    ): MapContextDungeonRoute {
        return new MapContextDungeonRoute(
            $this->cacheService,
            $this->coordinatesService,
            $dungeonRoute,
            $mapFacadeStyle,
        );
    }

    public function createMapContextLiveSession(LiveSession $liveSession, string $mapFacadeStyle): MapContextLiveSession
    {
        return new MapContextLiveSession(
            $this->cacheService,
            $this->coordinatesService,
            $this->overpulledEnemyService,
            $liveSession,
            $mapFacadeStyle,
        );
    }

    public function createMapContextDungeonExplore(
        Dungeon        $dungeon,
        MappingVersion $mappingVersion,
        string         $mapFacadeStyle,
    ): MapContextDungeonExplore {
        return new MapContextDungeonExplore(
            $this->cacheService,
            $this->coordinatesService,
            $this->seasonService,
            $dungeon,
            $mappingVersion,
            $mapFacadeStyle,
        );
    }

    public function createMapContextMappingVersionEdit(
        Dungeon        $dungeon,
        MappingVersion $mappingVersion,
    ): MapContextMappingVersionEdit {
        return new MapContextMappingVersionEdit(
            $this->cacheService,
            $this->coordinatesService,
            $dungeon,
            $mappingVersion,
        );
    }
}
