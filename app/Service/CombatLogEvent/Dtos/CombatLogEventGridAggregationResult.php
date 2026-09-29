<?php

namespace App\Service\CombatLogEvent\Dtos;

use App\Logic\Structs\IngameXY;
use App\Models\Floor\Floor;
use App\Models\Mapping\MappingVersion;
use App\Models\User;
use App\Service\Coordinates\CoordinatesServiceInterface;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;

/**
 * This class is used as a DTO to store the result of a CombatLogEvent aggregation (response from Opensearch).
 *
 * @implements Arrayable<string, mixed>
 */
class CombatLogEventGridAggregationResult implements Arrayable
{
    private bool $useFacade;

    private bool $useFacadeNavigation;

    private ?MappingVersion $currentMappingVersion = null;

    /**
     * @param array<int, array<int|string, int>> $results
     */
    public function __construct(
        private readonly CoordinatesServiceInterface $coordinatesService,
        private readonly CombatLogEventFilter        $combatLogEventFilter,
        private readonly array                       $results,
        private readonly int                         $runCount,
        private readonly bool                        $floorsAsArray = false,
    ) {
        $this->currentMappingVersion = $combatLogEventFilter->getDungeon()->getCurrentMappingVersion();
        $this->useFacade             = User::shouldUseFacadeMapStyle($this->currentMappingVersion);
        $this->useFacadeNavigation   = $this->useFacade && User::shouldUseFacadeNavigation($this->currentMappingVersion);
    }

    public function toArray(): array
    {
        $dungeon = $this->combatLogEventFilter->getDungeon();
        /** @var Collection<int, Floor> $floors */
        $floors = $dungeon->floors->keyBy('id');

        $weightMax          = 0;
        $data               = [];
        $navigableFloorData = [];
        foreach ($this->results as $floorId => $rows) {
            /** @var Floor $floor */
            $floor = $floors->get($floorId);

            $rawData = [];
            if ($this->floorsAsArray) {
                $rowCount = count($rows);
                for ($i = 0; $i < $rowCount; $i += 3) {
                    $rawData[] = [
                        $rows[$i],
                        $rows[$i + 1],
                        $rows[$i + 2],
                    ];
                }
            } else {
                foreach ($rows as $xy => $count) {
                    /**
                     * @var string $xy
                     * @var int    $count
                     */
                    $row   = explode(',', $xy);
                    $row[] = $count;

                    $rawData[] = $row;
                }
            }

            $latLngs      = [];
            $floorLatLngs = [];
            foreach ($rawData as $row) {
                [
                    $x,
                    $y,
                    $count,
                ] = $row;

                $ingameXY              = new IngameXY($x, $y, $floor);
                $latLngArray           = $this->convertIngameLocationToLatLngArray($ingameXY, $this->useFacade);
                $latLngArray['weight'] = $count;

                $latLngs[] = $latLngArray;
                if ($weightMax < $count) {
                    $weightMax = $count;
                }

                if ($this->useFacadeNavigation) {
                    $floorLatLngArray           = $this->convertIngameLocationToLatLngArray($ingameXY, false);
                    $floorLatLngArray['weight'] = $count;

                    $floorLatLngs[] = $floorLatLngArray;
                }
            }

            $data[$floorId] = [
                'floor_id' => $floorId,
                'lat_lngs' => $latLngs,
            ];

            if ($this->useFacadeNavigation) {
                $navigableFloorData[$floorId] = [
                    'floor_id' => $floorId,
                    'lat_lngs' => $floorLatLngs,
                ];
            }
        }

        // Do not split up by floors - but instead add it all to the facade floor instead
        if ($this->useFacade) {
            /** @var Floor|null $facadeFloor */
            $facadeFloor = $floors->where('facade', true)->first();
            if ($facadeFloor instanceof Floor) {
                $facadeData = [
                    $facadeFloor->id => [
                        'floor_id' => $facadeFloor->id,
                        'lat_lngs' => [],
                    ],
                ];

                $latLngsToCombine = [];
                foreach ($data as $floorId => $floorData) {
                    if ($floorId === $facadeFloor->id) {
                        // We're adding things to this floor, don't take from it!
                        continue;
                    }

                    $latLngsToCombine[] = $floorData['lat_lngs'];
                }

                $facadeData[$facadeFloor->id]['lat_lngs'] = array_merge(...$latLngsToCombine);
                // With facade navigation the viewer can also open the real floors, which need their own cells
                $data = $facadeData + $navigableFloorData;
            }
        }

        return [
            'data'        => array_values($data),
            'data_type'   => $this->combatLogEventFilter->getDataType(),
            'weight_max'  => $weightMax,
            'run_count'   => $this->runCount,
            'grid_size_x' => config('keystoneguru.heatmap.service.data.player.size_x'),
            'grid_size_y' => config('keystoneguru.heatmap.service.data.player.size_y'),
        ];

//        $useFacade = User::getCurrentUserMapFacadeStyle() === User::MAP_FACADE_STYLE_FACADE;
//
//        return [
//            'data'                => $this->combatLogEvents->map(function (CombatLogEvent $combatLogEvent)
//            use ($dungeon, $floors, $useFacade) {
//                $latLng = $this->coordinatesService->calculateMapLocationForIngameLocation(
//                    $combatLogEvent->getIngameXY()->setFloor($floors->get($combatLogEvent->ui_map_id))
//                );
//
//                $latLngArray = ($useFacade ?
//                    $this->coordinatesService->convertMapLocationToFacadeMapLocation($dungeon->currentMappingVersion, $latLng) :
//                    $latLng)->toArrayWithFloor();
//
//                $latLngArray['lat'] = round($latLngArray['lat'], 2);
//                $latLngArray['lng'] = round($latLngArray['lng'], 2);
//
//                return $latLngArray;
//            })->toArray(),
//            'dungeon_route_count' => $this->dungeonRouteCount,
//        ];
    }

    /**
     * @return array<string, float>
     */
    private function convertIngameLocationToLatLngArray(IngameXY $ingameXY, bool $useFacade): array
    {
        $dungeon = $this->combatLogEventFilter->getDungeon();

        // Just a little cache to avoid recalculating the mapping version every time
        $this->currentMappingVersion ??= $dungeon->getCurrentMappingVersion();

        $latLng = $this->coordinatesService->calculateMapLocationForIngameLocation($ingameXY);

        $latLngArray = ($useFacade ?
            $this->coordinatesService->convertMapLocationToFacadeMapLocation($this->currentMappingVersion, $latLng) :
            $latLng)->toArray();

        // Just limit the amount of data going out
        $latLngArray['lat'] = round($latLngArray['lat'], 2);
        $latLngArray['lng'] = round($latLngArray['lng'], 2);

        return $latLngArray;
    }

    /**
     * Only for unit tests really.
     *
     * @param  bool $useFacade
     * @return self
     */
    public function setUseFacade(bool $useFacade): self
    {
        $this->useFacade = $useFacade;

        return $this;
    }

    /**
     * Only for unit tests really.
     */
    public function setUseFacadeNavigation(bool $useFacadeNavigation): self
    {
        $this->useFacadeNavigation = $useFacadeNavigation;

        return $this;
    }
}
