<?php

namespace App\Http\Controllers\Javascript;

use App\Models\Dungeon;
use App\Models\Mapping\MappingVersion;
use App\Service\Cache\Traits\RemembersToFile;
use App\Service\MapContext\MapContextServiceInterface;
use Psr\SimpleCache\InvalidArgumentException;

class JavascriptController
{
    use RemembersToFile;

    /**
     * @throws InvalidArgumentException
     */
    public function mapContextMappingVersionData(
        MapContextServiceInterface $mapContextService,
        Dungeon                    $dungeon,
        MappingVersion             $mappingVersion,
        string                     $mapFacadeStyle,
    ) {
        $mapContextMappingVersionData = $mapContextService->createMapContextMappingVersionData(
            $dungeon,
            $mappingVersion,
            $mapFacadeStyle,
        );

        return sprintf('let mapContextMappingVersionData = %s;', json_encode($mapContextMappingVersionData->toArray(), JSON_PRETTY_PRINT));
    }

    /**
     * @throws InvalidArgumentException
     */
    public function mapContextDungeonData(
        MapContextServiceInterface $mapContextService,
        Dungeon                    $dungeon,
        string                     $locale,
    ) {
        $mapContextDungeonData = $mapContextService->createMapContextDungeonData(
            $dungeon,
            $locale,
        );

        return sprintf('let mapContextDungeonData = %s;', json_encode($mapContextDungeonData->toArray(), JSON_PRETTY_PRINT));
    }

    /**
     * @throws InvalidArgumentException
     */
    public function mapContextStaticData(
        MapContextServiceInterface $mapContextService,
        string                     $locale,
    ) {
        $mapContextStaticData = $mapContextService->createMapContextStaticData(
            $locale,
        );

        return sprintf('let mapContextStaticData = %s;', json_encode($mapContextStaticData->toArray(), JSON_PRETTY_PRINT));
    }
}
