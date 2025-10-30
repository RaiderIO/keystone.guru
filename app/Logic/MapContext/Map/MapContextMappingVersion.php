<?php

namespace App\Logic\MapContext\Map;

use App\Logic\MapContext\Map\MapContextBase;
use App\Models\Dungeon;
use App\Models\Faction;
use App\Models\Mapping\MappingVersion;
use App\Models\Npc\Npc;
use App\Service\Cache\CacheServiceInterface;
use App\Service\Coordinates\CoordinatesServiceInterface;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;

/**
 * Class MapContextMappingVersion
 *
 * @author  Wouter
 *
 * @since   06/08/2020
 */
abstract class MapContextMappingVersion extends MapContextBase
{
    public function __construct(
        CacheServiceInterface       $cacheService,
        CoordinatesServiceInterface $coordinatesService,
        Dungeon                     $dungeon,
        MappingVersion              $mappingVersion,
        string                      $mapFacadeStyle,
    ) {
        parent::__construct($cacheService, $coordinatesService, $dungeon, $mappingVersion, $mapFacadeStyle);
    }

    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'teeming'       => true,
            'seasonalIndex' => -1,
            // First should be unspecified
            'faction' => __(strtolower((string)Faction::where('key', Faction::FACTION_UNSPECIFIED)->first()->name)),
        ]);
    }
}
