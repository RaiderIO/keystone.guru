<?php

namespace App\Service\CombatLog;

use App\Models\CombatLog\CombatLogRouteEnemyFailure;
use App\Models\Dungeon;
use App\Service\CombatLog\Dtos\CombatLogRouteEnemyFailureHeatmapResult;
use App\Service\Coordinates\CoordinatesService;

class CombatLogRouteEnemyFailureService implements CombatLogRouteEnemyFailureServiceInterface
{
    /**
     * @param int[]|null $npcIds
     */
    public function getEnemyFailureHeatmapData(
        Dungeon $dungeon,
        ?array  $npcIds,
    ): CombatLogRouteEnemyFailureHeatmapResult {
        $gridSizeX = (int)config('keystoneguru.heatmap.service.data.player.size_x');
        $gridSizeY = (int)config('keystoneguru.heatmap.service.data.player.size_y');

        $query = CombatLogRouteEnemyFailure::query()->where('dungeon_id', $dungeon->id);

        if (!empty($npcIds)) {
            $query->whereIn('npc_id', $npcIds);
        }

        /** @var array<int, array<string, int>> $dataPerFloor */
        $dataPerFloor = [];
        $totalCount   = 0;

        foreach ($query->cursor() as $record) {
            /** @var CombatLogRouteEnemyFailure $record */
            $gridX = (int)floor(($record->lat / CoordinatesService::MAP_MAX_LAT) * $gridSizeX);
            $gridY = (int)floor(($record->lng / CoordinatesService::MAP_MAX_LNG) * $gridSizeY);
            $key   = sprintf('%d,%d', $gridX, $gridY);

            $dataPerFloor[$record->floor_id][$key] = ($dataPerFloor[$record->floor_id][$key] ?? 0) + 1;
            $totalCount++;
        }

        return new CombatLogRouteEnemyFailureHeatmapResult($dataPerFloor, $gridSizeX, $gridSizeY, $totalCount);
    }
}
