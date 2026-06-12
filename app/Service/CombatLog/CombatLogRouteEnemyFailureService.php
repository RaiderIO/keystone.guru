<?php

namespace App\Service\CombatLog;

use App\Models\CombatLog\CombatLogRouteEnemyFailure;
use App\Models\Dungeon;
use App\Models\DungeonRoute\DungeonRoute;
use App\Service\CombatLog\Dtos\CombatLogRouteEnemyFailureHeatmapResult;
use App\Service\Coordinates\CoordinatesService;
use App\Service\Coordinates\CoordinatesServiceInterface;

readonly class CombatLogRouteEnemyFailureService implements CombatLogRouteEnemyFailureServiceInterface
{
    public function __construct(private CoordinatesServiceInterface $coordinatesService)
    {
    }

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

        $dungeonRoutes = $this->getMatchingDungeonRoutes($dungeon, $npcIds);

        return new CombatLogRouteEnemyFailureHeatmapResult($this->coordinatesService, $dungeon, $dataPerFloor, $gridSizeX, $gridSizeY, $totalCount, $dungeonRoutes);
    }

    /**
     * @param  int[]|null                                                        $npcIds
     * @return array<int, array{public_key: string, title: string, url: string}>
     */
    private function getMatchingDungeonRoutes(Dungeon $dungeon, ?array $npcIds): array
    {
        if (empty($npcIds)) {
            return [];
        }

        $dungeonRouteIds = CombatLogRouteEnemyFailure::query()
            ->where('dungeon_id', $dungeon->id)
            ->whereNotNull('dungeon_route_id')
            ->whereIn('npc_id', $npcIds)
            ->distinct()
            ->limit(5)
            ->pluck('dungeon_route_id')
            ->all();

        if (empty($dungeonRouteIds)) {
            return [];
        }

        return DungeonRoute::with('dungeon')
            ->whereIn('id', $dungeonRouteIds)
            ->get()
            ->map(static fn(DungeonRoute $route) => [
                'public_key' => $route->public_key,
                'title'      => $route->title,
                'url'        => route('dungeonroute.view', [
                    'dungeon'      => $route->dungeon,
                    'dungeonroute' => $route,
                    'title'        => $route->getTitleSlug(),
                ]),
            ])
            ->values()
            ->all();
    }
}
