<?php

namespace App\Service\MapContext;

use App\Logic\MapContext\MapContextDungeonData;
use App\Logic\MapContext\MapContextDungeonExplore;
use App\Logic\MapContext\MapContextDungeonRoute;
use App\Logic\MapContext\MapContextLiveSession;
use App\Logic\MapContext\MapContextMappingVersionEdit;
use App\Models\Dungeon;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\LiveSession;
use App\Models\Mapping\MappingVersion;

interface MapContextServiceInterface
{
    public function createMapContextDungeonData(
        Dungeon        $dungeon,
        MappingVersion $mappingVersion,
        string         $mapFacadeStyle,
    ): MapContextDungeonData;

    public function createMapContextDungeonRoute(
        DungeonRoute $dungeonRoute,
        string $mapFacadeStyle,
    ): MapContextDungeonRoute;

    public function createMapContextLiveSession(
        LiveSession $liveSession,
        string $mapFacadeStyle,
    ): MapContextLiveSession;

    public function createMapContextDungeonExplore(
        Dungeon        $dungeon,
        MappingVersion $mappingVersion,
        string $mapFacadeStyle,
    ): MapContextDungeonExplore;

    public function createMapContextMappingVersionEdit(
        Dungeon        $dungeon,
        MappingVersion $mappingVersion,
    ): MapContextMappingVersionEdit;
}
