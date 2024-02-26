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

interface MapContextServiceInterface
{
    public function createMapContextDungeonRoute(
        DungeonRoute $dungeonRoute,
        Floor        $floor,
        ?string      $mapFacadeStyle = null
    ): MapContextDungeonRoute;

    public function createMapContextLiveSession(
        LiveSession $liveSession,
        Floor       $floor
    ): MapContextLiveSession;

    public function createMapContextDungeonExplore(
        Dungeon        $dungeon,
        Floor          $floor,
        MappingVersion $mappingVersion
    ): MapContextDungeonExplore;

    public function createMapContextMappingVersionEdit(
        Dungeon        $dungeon,
        Floor          $floor,
        MappingVersion $mappingVersion
    ): MapContextMappingVersionEdit;
}
