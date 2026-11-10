<?php

namespace App\Service\CombatLogEvent\Dtos;

use App\Logic\Structs\IngameXY;
use App\Logic\Structs\LatLng;
use App\Models\Floor\Floor;
use App\Models\Mapping\MappingVersion;
use App\Models\User;
use App\Service\Coordinates\CoordinatesService;
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

        if ($dungeon->isContinent()) {
            return $this->toContinentArray($floors);
        }

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
     * A continent's grid holds every zone floor at once, far more cells than any dungeon. Each zone floor converts
     * through one affine transform instead of per cell, and on the facade the cells of every zone are summed onto a
     * grid of the facade's own (keystoneguru.heatmap.continent.facade_grid_size_*), coarser than the zones' grids.
     *
     * @param  Collection<int, Floor> $floors
     * @return array<string, mixed>
     */
    private function toContinentArray(Collection $floors): array
    {
        /** @var Floor|null $facadeFloor */
        $facadeFloor = $this->useFacade ? $floors->firstWhere('facade', true) : null;
        $useFacade   = $facadeFloor instanceof Floor;
        $facadeGridSizeX = (int)config('keystoneguru.heatmap.continent.facade_grid_size_x');
        $facadeGridSizeY = (int)config('keystoneguru.heatmap.continent.facade_grid_size_y');

        $weightMax        = 0;
        $data             = [];
        $facadeGridWeights = [];
        foreach ($this->results as $floorId => $rows) {
            /** @var Floor|null $floor */
            $floor = $floors->get($floorId);
            if ($floor === null || $floor->facade) {
                continue;
            }

            $rawData = $this->getRawData($rows);

            if ($useFacade) {
                $toFacadeLatLng = $this->getIngameToLatLngTransform($floor, true);
                foreach ($rawData as [$x, $y, $count]) {
                    [$lat, $lng] = $toFacadeLatLng((float)$x, (float)$y);

                    $gridX = (int)floor(($lat / CoordinatesService::MAP_MAX_LAT) * $facadeGridSizeX);
                    $gridY = (int)floor(($lng / CoordinatesService::MAP_MAX_LNG) * $facadeGridSizeY);
                    if ($gridX < 0 || $gridX >= $facadeGridSizeX || $gridY < 0 || $gridY >= $facadeGridSizeY) {
                        continue;
                    }

                    $cellIndex                     = $gridX * $facadeGridSizeY + $gridY;
                    $facadeGridWeights[$cellIndex] = ($facadeGridWeights[$cellIndex] ?? 0) + (int)$count;
                }
            }

            if (!$useFacade || $this->useFacadeNavigation) {
                $toLatLng = $this->getIngameToLatLngTransform($floor, false);
                $latLngs  = [];
                foreach ($rawData as [$x, $y, $count]) {
                    [$lat, $lng] = $toLatLng((float)$x, (float)$y);

                    $latLngs[] = [
                        'lat'    => round($lat, 2),
                        'lng'    => round($lng, 2),
                        'weight' => (int)$count,
                    ];
                    $weightMax = max($weightMax, (int)$count);
                }

                $data[$floorId] = [
                    'floor_id' => $floorId,
                    'lat_lngs' => $latLngs,
                ];
            }
        }

        if ($useFacade) {
            $facadeLatLngs     = [];
            $facadeWeightMax   = 0;
            foreach ($facadeGridWeights as $cellIndex => $weight) {
                $facadeLatLngs[] = [
                    'lat'    => round(((intdiv($cellIndex, $facadeGridSizeY) + 0.5) / $facadeGridSizeX) * CoordinatesService::MAP_MAX_LAT, 2),
                    'lng'    => round(((($cellIndex % $facadeGridSizeY) + 0.5) / $facadeGridSizeY) * CoordinatesService::MAP_MAX_LNG, 2),
                    'weight' => $weight,
                ];
                $facadeWeightMax = max($facadeWeightMax, $weight);
            }

            $data = [
                $facadeFloor->id => [
                    'floor_id'    => $facadeFloor->id,
                    'lat_lngs'    => $facadeLatLngs,
                    'grid_size_x' => $facadeGridSizeX,
                    'grid_size_y' => $facadeGridSizeY,
                    'weight_max'  => $facadeWeightMax,
                ],
            ] + $data;
            $weightMax = max($weightMax, $facadeWeightMax);
        }

        return [
            'data'        => array_values($data),
            'data_type'   => $this->combatLogEventFilter->getDataType(),
            'weight_max'  => $weightMax,
            'run_count'   => $this->runCount,
            'grid_size_x' => config('keystoneguru.heatmap.service.data.player.size_x'),
            'grid_size_y' => config('keystoneguru.heatmap.service.data.player.size_y'),
        ];
    }

    /**
     * @param  array<int|string, int|float> $rows
     * @return list<array{0: int|float|string, 1: int|float|string, 2: int|float}>
     */
    private function getRawData(array $rows): array
    {
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
                $row   = explode(',', (string)$xy);
                $row[] = $count;

                $rawData[] = $row;
            }
        }

        return $rawData;
    }

    /**
     * Converting an in-game location to a map location, and from there to the facade through a single floor union, is
     * affine: derive it once from three corners of the floor through the regular conversion and apply it per cell. A
     * floor with more than one floor union picks one per location, so that one converts each cell on its own.
     *
     * @return callable(float, float): array{0: float, 1: float}
     */
    private function getIngameToLatLngTransform(Floor $floor, bool $useFacade): callable
    {
        if ($useFacade && $this->currentMappingVersion?->getFloorUnionsForFloor($floor)->count() > 1) {
            return function (float $x, float $y) use ($floor): array {
                $latLng = $this->convertIngameLocationToLatLng(new IngameXY($x, $y, $floor), true);

                return [$latLng->getLat(), $latLng->getLng()];
            };
        }

        $minX = $floor->ingame_min_x;
        $minY = $floor->ingame_min_y;
        $width  = $floor->ingame_max_x - $minX;
        $height = $floor->ingame_max_y - $minY;

        $origin = $this->convertIngameLocationToLatLng(new IngameXY($minX, $minY, $floor), $useFacade);
        $alongX = $this->convertIngameLocationToLatLng(new IngameXY($minX + $width, $minY, $floor), $useFacade);
        $alongY = $this->convertIngameLocationToLatLng(new IngameXY($minX, $minY + $height, $floor), $useFacade);

        $latPerX = ($alongX->getLat() - $origin->getLat()) / $width;
        $lngPerX = ($alongX->getLng() - $origin->getLng()) / $width;
        $latPerY = ($alongY->getLat() - $origin->getLat()) / $height;
        $lngPerY = ($alongY->getLng() - $origin->getLng()) / $height;
        $originLat = $origin->getLat();
        $originLng = $origin->getLng();

        return static fn(float $x, float $y): array => [
            $originLat + ($x - $minX) * $latPerX + ($y - $minY) * $latPerY,
            $originLng + ($x - $minX) * $lngPerX + ($y - $minY) * $lngPerY,
        ];
    }

    private function convertIngameLocationToLatLng(IngameXY $ingameXY, bool $useFacade): LatLng
    {
        $latLng = $this->coordinatesService->calculateMapLocationForIngameLocation($ingameXY);

        return $useFacade ?
            $this->coordinatesService->convertMapLocationToFacadeMapLocation($this->currentMappingVersion, $latLng) :
            $latLng;
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
