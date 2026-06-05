<?php

namespace App\Service\MapContext;

use App\Logic\MapContext\Map\MapContextDungeonExplore;
use App\Logic\MapContext\Map\MapContextDungeonRoute;
use App\Logic\MapContext\Map\MapContextDungeonRouteSearch;
use App\Logic\MapContext\Map\MapContextLiveSession;
use App\Logic\MapContext\Map\MapContextMappingVersionEdit;
use App\Logic\MapContext\MapContextDungeonData;
use App\Logic\MapContext\MapContextMappingVersionData;
use App\Logic\MapContext\MapContextStaticData;
use App\Models\Dungeon;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\LiveSession;
use App\Models\Mapping\MappingVersion;
use App\Service\Cache\CacheServiceInterface;
use App\Service\Coordinates\CoordinatesServiceInterface;
use App\Service\KillZonePath\KillZonePathServiceInterface;
use App\Service\LiveSession\OverpulledEnemyServiceInterface;
use App\Service\Season\SeasonAffixGroupServiceInterface;
use App\Service\Season\SeasonServiceInterface;

/**
 * @TODO Add caching layer here, instead of in the map context classes?
 */
readonly class MapContextService implements MapContextServiceInterface
{
    public function __construct(
        private CacheServiceInterface            $cacheService,
        private CoordinatesServiceInterface      $coordinatesService,
        private KillZonePathServiceInterface     $killZonePathService,
        private OverpulledEnemyServiceInterface  $overpulledEnemyService,
        private SeasonServiceInterface           $seasonService,
        private SeasonAffixGroupServiceInterface $seasonAffixGroupService,
    ) {
    }

    public function createMapContextMappingVersionData(
        Dungeon        $dungeon,
        MappingVersion $mappingVersion,
        string         $mapFacadeStyle,
    ): MapContextMappingVersionData {
        return new MapContextMappingVersionData(
            $this->cacheService,
            $this->coordinatesService,
            $dungeon,
            $mappingVersion,
            $mapFacadeStyle,
        );
    }

    public function createMapContextDungeonData(Dungeon $dungeon, string $locale): MapContextDungeonData
    {
        return new MapContextDungeonData(
            $this->cacheService,
            $this->coordinatesService,
            $dungeon,
            $locale,
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
            $this->killZonePathService,
            $dungeonRoute,
            $mapFacadeStyle,
        );
    }

    public function createMapContextLiveSession(LiveSession $liveSession, string $mapFacadeStyle): MapContextLiveSession
    {
        return new MapContextLiveSession(
            $this->cacheService,
            $this->coordinatesService,
            $this->killZonePathService,
            $this->overpulledEnemyService,
            $liveSession,
            $mapFacadeStyle,
        );
    }

    public function createMapContextDungeonRouteSearch(
        Dungeon        $dungeon,
        MappingVersion $mappingVersion,
        string         $mapFacadeStyle,
    ): MapContextDungeonRouteSearch {
        return new MapContextDungeonRouteSearch(
            $this->cacheService,
            $this->coordinatesService,
            $this->seasonService,
            $this->seasonAffixGroupService,
            $dungeon,
            $mappingVersion,
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
            $this->seasonAffixGroupService,
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
