<?php

namespace App\Service\CombatLogEvent\Models;

use App\Logic\Structs\IngameXY;
use App\Models\Floor\Floor;
use App\Models\User;
use App\Service\Coordinates\CoordinatesServiceInterface;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;

class CombatLogEventGridAggregationResult implements Arrayable
{
    private bool $useFacade;

    public function __construct(
        private readonly CoordinatesServiceInterface $coordinatesService,
        private readonly CombatLogEventFilter        $combatLogEventFilter,
        private readonly array                       $results,
        private readonly int                         $runCount
    ) {
        $this->useFacade = User::getCurrentUserMapFacadeStyle() === User::MAP_FACADE_STYLE_FACADE;
    }

    public function toArray(): array
    {
        $dungeon = $this->combatLogEventFilter->getDungeon();
        /** @var Collection<Floor> $floors */
        $floors = $dungeon->floors->keyBy('id');

        $data = [];
        foreach ($this->results as $floorId => $rows) {
            /** @var Floor $floor */
            $floor = $floors->get($floorId);

            $latLngs = [];

            foreach ($rows as $xy => $count) {
                /**
                 * @var string $xy
                 * @var int    $count
                 */
                [$x, $y] = explode(',', $xy);

                $latLngArray           = $this->convertIngameLocationToLatLngArray(new IngameXY($x, $y, $floor));
                $latLngArray['weight'] = $count;

                $latLngs[] = $latLngArray;
            }

            $data[$floorId] = [
                'floor_id' => $floorId,
                'lat_lngs' => $latLngs,
            ];
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
                $data                                     = $facadeData;
            }
        }

        return [
            'data'      => array_values($data),
            'run_count' => $this->runCount,
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

    private function convertIngameLocationToLatLngArray(IngameXY $ingameXY): array
    {
        $dungeon = $this->combatLogEventFilter->getDungeon();

        $latLng = $this->coordinatesService->calculateMapLocationForIngameLocation($ingameXY);

        $latLngArray = ($this->useFacade ?
            $this->coordinatesService->convertMapLocationToFacadeMapLocation($dungeon->currentMappingVersion, $latLng) :
            $latLng)->toArray();

        // Just limit the amount of data going out
        $latLngArray['lat'] = round($latLngArray['lat'], 2);
        $latLngArray['lng'] = round($latLngArray['lng'], 2);

        return $latLngArray;
    }
}
