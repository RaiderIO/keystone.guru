<?php

namespace App\Service\MapContext;

use App\Logic\MapContext\MapContextDungeonData;
use App\Logic\MapContext\MapContextMappingVersionData;
use App\Logic\MapContext\Map\MapContextDungeonExplore;
use App\Logic\MapContext\Map\MapContextDungeonRoute;
use App\Logic\MapContext\Map\MapContextLiveSession;
use App\Logic\MapContext\Map\MapContextMappingVersionEdit;
use App\Logic\MapContext\MapContextStaticData;
use App\Models\Dungeon;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\LiveSession;
use App\Models\Mapping\MappingVersion;

interface MapContextServiceInterface
{
    public function createMapContextStaticData(
        string $locale
    ): MapContextStaticData;

    public function createMapContextMappingVersionData(
        Dungeon        $dungeon,
        MappingVersion $mappingVersion,
        string         $mapFacadeStyle,
    ): MapContextMappingVersionData;

    public function createMapContextDungeonData(
        Dungeon $dungeon,
        string  $locale,
    ): MapContextDungeonData;

    public function createMapContextDungeonRoute(
        DungeonRoute $dungeonRoute,
        string       $mapFacadeStyle,
    ): MapContextDungeonRoute;

    public function createMapContextLiveSession(
        LiveSession $liveSession,
        string      $mapFacadeStyle,
    ): MapContextLiveSession;

    public function createMapContextDungeonExplore(
        Dungeon        $dungeon,
        MappingVersion $mappingVersion,
        string         $mapFacadeStyle,
    ): MapContextDungeonExplore;

    public function createMapContextMappingVersionEdit(
        Dungeon        $dungeon,
        MappingVersion $mappingVersion,
    ): MapContextMappingVersionEdit;
}
