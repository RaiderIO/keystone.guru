<?php

namespace App\Service\RaiderIO;

use App\Models\Dungeon;
use App\Models\Mapping\MappingVersion;

interface HeatmapHotspotServiceInterface
{
    /**
     * Converts the hotspots of a Raider.IO heatmap grid response to the lat/lngs of the map that is going to show them,
     * shaped like HeatmapDataResponse's hotspots: one entry per floor the viewer can open.
     *
     * @param  array<int|string, mixed>                                         $hotspotsByFloor The raw hotspotsByFloor field, keyed by zone floor id
     * @return list<array{floor_id: int, hotspots: list<array<string, mixed>>}>
     */
    public function convertHotspotsByFloor(
        Dungeon         $dungeon,
        ?MappingVersion $mappingVersion,
        array           $hotspotsByFloor,
        bool            $useFacade,
        bool            $useFacadeNavigation,
    ): array;
}
