<?php

namespace App\Service\MapContext;

use App\Logic\MapContext\MapContextDungeonExplore;
use App\Logic\MapContext\MapContextDungeonRoute;
use App\Logic\MapContext\MapContextLiveSession;
use App\Logic\MapContext\MapContextMappingVersionEdit;
use App\Models\Dungeon;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Floor\Floor;
use App\Models\LiveSession;
use App\Models\Mapping\MappingVersion;
use App\Service\Cache\CacheServiceInterface;
use App\Service\Coordinates\CoordinatesServiceInterface;
use App\Service\LiveSession\OverpulledEnemyServiceInterface;
use App\Service\Season\SeasonServiceInterface;

class MapContextService implements MapContextServiceInterface
{
    public function __construct(
        private readonly CacheServiceInterface           $cacheService,
        private readonly CoordinatesServiceInterface     $coordinatesService,
        private readonly OverpulledEnemyServiceInterface $overpulledEnemyService,
        private readonly SeasonServiceInterface          $seasonService,
    ) {
    }

    public function createMapContextDungeonRoute(
        DungeonRoute $dungeonRoute,
        ?string      $mapFacadeStyle = null,
    ): MapContextDungeonRoute {
        return new MapContextDungeonRoute(
            $this->cacheService,
            $this->coordinatesService,
            $dungeonRoute,
            $mapFacadeStyle,
        );
    }

    public function createMapContextLiveSession(LiveSession $liveSession): MapContextLiveSession
    {
        return new MapContextLiveSession(
            $this->cacheService,
            $this->coordinatesService,
            $this->overpulledEnemyService,
            $liveSession,
        );
    }

    public function createMapContextDungeonExplore(
        Dungeon        $dungeon,
        MappingVersion $mappingVersion,
    ): MapContextDungeonExplore {
        return new MapContextDungeonExplore(
            $this->cacheService,
            $this->coordinatesService,
            $this->seasonService,
            $dungeon,
            $mappingVersion,
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
