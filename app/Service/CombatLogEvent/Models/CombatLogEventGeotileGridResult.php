<?php

namespace App\Service\CombatLogEvent\Models;

use App\Logic\Structs\IngameXY;
use App\Logic\Structs\LatLng;
use App\Models\CombatLog\CombatLogEvent;
use App\Models\Floor\Floor;
use App\Models\User;
use App\Service\Coordinates\CoordinatesServiceInterface;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;
use function Deployer\Support\array_flatten;

class CombatLogEventGeotileGridResult implements Arrayable
{
    public function __construct(
        private readonly CoordinatesServiceInterface $coordinatesService,
        private readonly CombatLogEventFilter        $combatLogEventFilter,
        private readonly array                       $buckets,
        private readonly int                         $dungeonRouteCount
    ) {

    }

    public function toArray(): array
    {
        $dungeon = $this->combatLogEventFilter->getDungeon();
        /** @var Collection<Floor> $floors */
        $floors = $dungeon->floors->keyBy('ui_map_id');


        $data = [];
        foreach ($this->buckets as $bucket) {
            /** @var array{key: int, doc_count: int, grid: array{buckets: array}} $bucket */

            /** @var Floor $floor */
            $floor            = $floors->get($bucket['key']);


            $data[] = array_map(function ($xyBucket) use($floor) {
                /** @var array{key: string, doc_count: int} $xyBucket */
                // 8/141/114 for example
                [$precision, $x, $y] = explode('/', $xyBucket['key']);

                $latLngArray = $this->convertGridLocationToLatLngArray($floor, $precision, $x, $y);
                $latLngArray['weight'] = $xyBucket['doc_count'];

                return $latLngArray;
            }, $bucket['grid']['buckets']);
        }

        return [
            'data'                => array_merge(...$data),
            'dungeon_route_count' => $this->dungeonRouteCount,
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

    private function convertGridLocationToLatLngArray(Floor $floor, int $precision, int $gridX, int $gridY): array
    {
        $dungeon = $this->combatLogEventFilter->getDungeon();

        $floorWidth = $floor->ingame_max_x - $floor->ingame_min_x;
        $floorHeight = $floor->ingame_max_y - $floor->ingame_min_y;
        $tileSize = 1.406;

        $ingameXY = new IngameXY($floor->ingame_min_x + ($gridX * $tileSize), $floor->ingame_min_y + ($gridY * $tileSize), $floor);

        $useFacade = User::getCurrentUserMapFacadeStyle() === User::MAP_FACADE_STYLE_FACADE;

        $latLng = $this->coordinatesService->calculateMapLocationForIngameLocation(
            $ingameXY
        );

        $latLngArray = ($useFacade ?
            $this->coordinatesService->convertMapLocationToFacadeMapLocation($dungeon->currentMappingVersion, $latLng) :
            $latLng)->toArrayWithFloor();

        $latLngArray['lat'] = round($latLngArray['lat'], 2);
        $latLngArray['lng'] = round($latLngArray['lng'], 2);

        return $latLngArray;
    }
}
