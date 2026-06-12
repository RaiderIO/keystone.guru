<?php

namespace App\Service\CombatLog\Dtos;

use App\Logic\Structs\LatLng;
use App\Models\Dungeon;
use App\Models\Floor\Floor;
use App\Models\Mapping\MappingVersion;
use App\Models\User;
use App\Service\Coordinates\CoordinatesService;
use App\Service\Coordinates\CoordinatesServiceInterface;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;

/**
 * @implements Arrayable<string, mixed>
 */
class CombatLogRouteEnemyFailureHeatmapResult implements Arrayable
{
    public const string DATA_TYPE = 'combat_log_route_enemy_failure';

    private bool $useFacade;

    private ?MappingVersion $currentMappingVersion = null;

    /**
     * @param array<int, array<string, int>>                                    $dataPerFloor  floor_id => ['gridX,gridY' => count]
     * @param array<int, array{public_key: string, title: string, url: string}> $dungeonRoutes
     */
    public function __construct(
        private readonly CoordinatesServiceInterface $coordinatesService,
        private readonly Dungeon                     $dungeon,
        private readonly array                       $dataPerFloor,
        private readonly int                         $gridSizeX,
        private readonly int                         $gridSizeY,
        private readonly int                         $failureCount,
        private readonly array                       $dungeonRoutes = [],
    ) {
        $this->useFacade = User::getCurrentUserMapFacadeStyle() === User::MAP_FACADE_STYLE_FACADE;
    }

    public function toArray(): array
    {
        /** @var Collection<int, Floor> $floors */
        $floors = $this->dungeon->floors->keyBy('id');

        $weightMax = 0;
        $data      = [];

        foreach ($this->dataPerFloor as $floorId => $gridCells) {
            /** @var Floor|null $floor */
            $floor   = $floors->get($floorId);
            $latLngs = [];

            foreach ($gridCells as $gridKey => $count) {
                [$gridX, $gridY] = explode(',', $gridKey);

                $lat = (((int)$gridX + 0.5) / $this->gridSizeX) * CoordinatesService::MAP_MAX_LAT;
                $lng = (((int)$gridY + 0.5) / $this->gridSizeY) * CoordinatesService::MAP_MAX_LNG;

                if ($this->useFacade && $floor !== null) {
                    $this->currentMappingVersion ??= $this->dungeon->getCurrentMappingVersion();
                    $converted = $this->coordinatesService->convertMapLocationToFacadeMapLocation(
                        $this->currentMappingVersion,
                        new LatLng($lat, $lng, $floor),
                    );
                    $lat = $converted->getLat();
                    $lng = $converted->getLng();
                }

                $latLngs[] = [
                    'lat'    => round($lat, 2),
                    'lng'    => round($lng, 2),
                    'weight' => $count,
                ];

                if ($weightMax < $count) {
                    $weightMax = $count;
                }
            }

            $data[$floorId] = [
                'floor_id' => $floorId,
                'lat_lngs' => $latLngs,
            ];
        }

        if ($this->useFacade) {
            /** @var Floor|null $facadeFloor */
            $facadeFloor = $floors->where('facade', true)->first();
            if ($facadeFloor instanceof Floor) {
                $latLngsToCombine = [];
                foreach ($data as $floorId => $floorData) {
                    if ($floorId === $facadeFloor->id) {
                        continue;
                    }

                    $latLngsToCombine[] = $floorData['lat_lngs'];
                }

                $data = [
                    $facadeFloor->id => [
                        'floor_id' => $facadeFloor->id,
                        'lat_lngs' => empty($latLngsToCombine) ? [] : array_merge(...$latLngsToCombine),
                    ],
                ];
            }
        }

        return [
            'data'           => array_values($data),
            'data_type'      => self::DATA_TYPE,
            'weight_max'     => $weightMax,
            'failure_count'  => $this->failureCount,
            'grid_size_x'    => $this->gridSizeX,
            'grid_size_y'    => $this->gridSizeY,
            'dungeon_routes' => $this->dungeonRoutes,
        ];
    }

    /**
     * Only for unit tests.
     */
    public function setUseFacade(bool $useFacade): self
    {
        $this->useFacade = $useFacade;

        return $this;
    }
}
