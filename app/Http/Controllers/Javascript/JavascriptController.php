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
    public function mapContextDungeonData(
        MapContextServiceInterface $mapContextService,
        Dungeon                    $dungeon,
        MappingVersion             $mappingVersion,
        string                     $mapFacadeStyle,
    ) {
        $mapContextDungeonData = $mapContextService->createMapContextDungeonData(
            $dungeon,
            $mappingVersion,
            $mapFacadeStyle,
        );

        return sprintf('let mapContextDungeonData = %s;', json_encode($mapContextDungeonData->toArray(), JSON_PRETTY_PRINT));
    }
}
