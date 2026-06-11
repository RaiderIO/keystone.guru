<?php

namespace App\Service\CombatLog\Dtos;

use App\Service\Coordinates\CoordinatesService;
use Illuminate\Contracts\Support\Arrayable;

/**
 * @implements Arrayable<string, mixed>
 */
class CombatLogRouteEnemyFailureHeatmapResult implements Arrayable
{
    public const string DATA_TYPE = 'combat_log_route_enemy_failure';

    /**
     * @param array<int, array<string, int>> $dataPerFloor floor_id => ['gridX,gridY' => count]
     */
    public function __construct(
        private readonly array $dataPerFloor,
        private readonly int   $gridSizeX,
        private readonly int   $gridSizeY,
        private readonly int   $failureCount,
    ) {
    }

    public function toArray(): array
    {
        $weightMax = 0;
        $data      = [];

        foreach ($this->dataPerFloor as $floorId => $gridCells) {
            $latLngs = [];

            foreach ($gridCells as $gridKey => $count) {
                [$gridX, $gridY] = explode(',', $gridKey);

                $latLngs[] = [
                    'lat'    => round((((int)$gridX + 0.5) / $this->gridSizeX) * CoordinatesService::MAP_MAX_LAT, 2),
                    'lng'    => round((((int)$gridY + 0.5) / $this->gridSizeY) * CoordinatesService::MAP_MAX_LNG, 2),
                    'weight' => $count,
                ];

                if ($weightMax < $count) {
                    $weightMax = $count;
                }
            }

            $data[] = [
                'floor_id' => $floorId,
                'lat_lngs' => $latLngs,
            ];
        }

        return [
            'data'          => $data,
            'data_type'     => self::DATA_TYPE,
            'weight_max'    => $weightMax,
            'failure_count' => $this->failureCount,
            'grid_size_x'   => $this->gridSizeX,
            'grid_size_y'   => $this->gridSizeY,
        ];
    }
}
