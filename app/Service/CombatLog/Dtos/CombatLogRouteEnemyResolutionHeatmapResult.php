<?php

namespace App\Service\CombatLog\Dtos;

use App\Logic\Structs\LatLng;
use App\Models\Dungeon;
use App\Models\Floor\Floor;
use App\Models\Mapping\MappingVersion;
use App\Models\User;
use App\Service\CombatLog\Enums\EnemyResolutionHeatmapMetric;
use App\Service\Coordinates\CoordinatesService;
use App\Service\Coordinates\CoordinatesServiceInterface;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;

/**
 * The grid the enemy resolution distance heatmap draws. Unlike the enemy failure heatmap a cell's weight is a distance
 * rather than a count: how far off the matches recorded in that cell were, in ingame yards.
 *
 * @phpstan-type GridCell array{count: int, sum: float, max: float}
 *
 * @implements Arrayable<string, mixed>
 */
class CombatLogRouteEnemyResolutionHeatmapResult implements Arrayable
{
    public const string DATA_TYPE = 'combat_log_route_enemy_resolution';

    private bool $useFacade;

    /**
     * @param array<int, array<string, GridCell>>                               $dataPerFloor  floor_id => ['gridX,gridY' => cell]
     * @param int                                                               $minSamples    cells with fewer matches than this are not drawn at all
     * @param array<int, array{public_key: string, title: string, url: string}> $dungeonRoutes
     */
    public function __construct(
        private readonly CoordinatesServiceInterface  $coordinatesService,
        private readonly Dungeon                      $dungeon,
        private readonly MappingVersion               $mappingVersion,
        private readonly array                        $dataPerFloor,
        private readonly int                          $gridSizeX,
        private readonly int                          $gridSizeY,
        private readonly int                          $resolutionCount,
        private readonly EnemyResolutionHeatmapMetric $metric,
        private readonly int                          $minSamples,
        private readonly array                        $dungeonRoutes = [],
    ) {
        $this->useFacade = User::shouldUseFacadeMapStyle($mappingVersion);
    }

    public function toArray(): array
    {
        /** @var Collection<int, Floor> $floors */
        $floors = $this->dungeon->floors->keyBy('id');

        $weightMax  = 0.0;
        $data       = [];
        $drawnCount = 0;

        foreach ($this->dataPerFloor as $floorId => $gridCells) {
            /** @var Floor|null $floor */
            $floor   = $floors->get($floorId);
            $latLngs = [];

            foreach ($gridCells as $gridKey => $gridCell) {
                // One body pull leaves a single far match anywhere; only a spot that is off again and again is
                // evidence about the mapping, so a cell has to earn its place before it is drawn
                if ($gridCell['count'] < $this->minSamples) {
                    continue;
                }

                [$gridX, $gridY] = explode(',', $gridKey);

                $lat = (((int)$gridX + 0.5) / $this->gridSizeX) * CoordinatesService::MAP_MAX_LAT;
                $lng = (((int)$gridY + 0.5) / $this->gridSizeY) * CoordinatesService::MAP_MAX_LNG;

                if ($this->useFacade && $floor !== null) {
                    // Floor unions are per mapping version - convert with the one the resolutions were recorded against
                    $converted = $this->coordinatesService->convertMapLocationToFacadeMapLocation(
                        $this->mappingVersion,
                        new LatLng($lat, $lng, $floor),
                    );
                    $lat = $converted->getLat();
                    $lng = $converted->getLng();
                }

                $weight = match ($this->metric) {
                    EnemyResolutionHeatmapMetric::Average => $gridCell['sum'] / $gridCell['count'],
                    EnemyResolutionHeatmapMetric::Max     => $gridCell['max'],
                };

                $latLngs[] = [
                    'lat'    => round($lat, 2),
                    'lng'    => round($lng, 2),
                    'weight' => round($weight, 2),
                    'count'  => $gridCell['count'],
                ];
                $drawnCount += $gridCell['count'];

                if ($weightMax < $weight) {
                    $weightMax = $weight;
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
            'data'             => array_values($data),
            'data_type'        => self::DATA_TYPE,
            'weight_max'       => round($weightMax, 2),
            'resolution_count' => $this->resolutionCount,
            'drawn_count'      => $drawnCount,
            'metric'           => $this->metric->value,
            'min_samples'      => $this->minSamples,
            'grid_size_x'      => $this->gridSizeX,
            'grid_size_y'      => $this->gridSizeY,
            'dungeon_routes'   => $this->dungeonRoutes,
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
