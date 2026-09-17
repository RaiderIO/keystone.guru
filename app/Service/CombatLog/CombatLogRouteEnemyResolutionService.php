<?php

namespace App\Service\CombatLog;

use App\Models\CombatLog\CombatLogRouteEnemyResolution;
use App\Models\Dungeon;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Mapping\MappingVersion;
use App\Service\CombatLog\Dtos\CombatLogRouteEnemyResolutionHeatmapResult;
use App\Service\CombatLog\Enums\EnemyResolutionHeatmapMetric;
use App\Service\Coordinates\CoordinatesService;
use App\Service\Coordinates\CoordinatesServiceInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

readonly class CombatLogRouteEnemyResolutionService implements CombatLogRouteEnemyResolutionServiceInterface
{
    /** How many routes the sidebar offers to inspect for the current filter. */
    private const int MATCHING_DUNGEON_ROUTES_LIMIT = 5;

    public function __construct(private CoordinatesServiceInterface $coordinatesService)
    {
    }

    public function getResolutionHeatmapData(
        Dungeon                      $dungeon,
        MappingVersion               $mappingVersion,
        ?array                       $npcIds,
        EnemyResolutionHeatmapMetric $metric,
        ?float                       $minDistance = null,
    ): CombatLogRouteEnemyResolutionHeatmapResult {
        $gridSizeX  = (int)config('keystoneguru.heatmap.service.data.player.size_x');
        $gridSizeY  = (int)config('keystoneguru.heatmap.service.data.player.size_y');
        $minSamples = (int)config('keystoneguru.enemy_resolution.heatmap_min_samples');

        $query = $this->resolutionsQuery($dungeon, $mappingVersion, $npcIds, $minDistance);

        /** @var array<int, array<string, array{count: int, sum: float, max: float}>> $dataPerFloor */
        $dataPerFloor = [];
        $totalCount   = 0;

        foreach ($query->cursor() as $record) {
            /** @var CombatLogRouteEnemyResolution $record */
            $gridX = (int)floor(($record->lat / CoordinatesService::MAP_MAX_LAT) * $gridSizeX);
            $gridY = (int)floor(($record->lng / CoordinatesService::MAP_MAX_LNG) * $gridSizeY);
            $key   = sprintf('%d,%d', $gridX, $gridY);

            $distance = (float)$record->weighted_distance;
            $cell     = $dataPerFloor[$record->floor_id][$key] ?? ['count' => 0, 'sum' => 0.0, 'max' => 0.0];

            $dataPerFloor[$record->floor_id][$key] = [
                'count' => $cell['count'] + 1,
                'sum'   => $cell['sum'] + $distance,
                'max'   => max($cell['max'], $distance),
            ];
            $totalCount++;
        }

        return new CombatLogRouteEnemyResolutionHeatmapResult(
            $this->coordinatesService,
            $dungeon,
            $mappingVersion,
            $dataPerFloor,
            $gridSizeX,
            $gridSizeY,
            $totalCount,
            $metric,
            $minSamples,
            $this->getMatchingDungeonRoutes($dungeon, $mappingVersion, $npcIds, $minDistance),
        );
    }

    public function getResolutionCountsPerNpc(Dungeon $dungeon, MappingVersion $mappingVersion): Collection
    {
        /** @var Collection<int, int> $result */
        $result = $this->resolutionsQuery($dungeon, $mappingVersion, null, null)
            ->whereNotNull('npc_id')
            ->selectRaw('npc_id, COUNT(*) AS resolution_count')
            ->groupBy('npc_id')
            ->orderByDesc('resolution_count')
            ->pluck('resolution_count', 'npc_id')
            ->map(static fn($count): int => (int)$count);

        return $result;
    }

    public function getResolutionCountsPerMappingVersion(Dungeon $dungeon): Collection
    {
        /** @var array<int, int> $counts */
        $counts = CombatLogRouteEnemyResolution::query()
            ->where('dungeon_id', $dungeon->id)
            ->selectRaw('mapping_version_id, COUNT(*) AS resolution_count')
            ->groupBy('mapping_version_id')
            ->pluck('resolution_count', 'mapping_version_id')
            ->map(static fn($count): int => (int)$count)
            ->all();

        /** @var Collection<int, int> $result */
        $result = $dungeon->mappingVersions
            ->mapWithKeys(static fn(MappingVersion $mappingVersion) => [$mappingVersion->id => $counts[$mappingVersion->id] ?? 0]);

        return $result;
    }

    /**
     * @param  int[]|null                             $npcIds
     * @return Builder<CombatLogRouteEnemyResolution>
     */
    private function resolutionsQuery(Dungeon $dungeon, MappingVersion $mappingVersion, ?array $npcIds, ?float $minDistance): Builder
    {
        return CombatLogRouteEnemyResolution::query()
            ->where('dungeon_id', $dungeon->id)
            ->where('mapping_version_id', $mappingVersion->id)
            ->when(!empty($npcIds), static fn(Builder $builder) => $builder->whereIn('npc_id', $npcIds))
            ->when($minDistance !== null, static fn(Builder $builder) => $builder->where('weighted_distance', '>=', $minDistance));
    }

    /**
     * The routes behind the current filter, worst matches first - the point of the page is being able to open one of
     * them and see what the Auto Route Creator made of it.
     *
     * @param  int[]|null                                                        $npcIds
     * @return array<int, array{public_key: string, title: string, url: string}>
     */
    private function getMatchingDungeonRoutes(Dungeon $dungeon, MappingVersion $mappingVersion, ?array $npcIds, ?float $minDistance): array
    {
        if (empty($npcIds)) {
            return [];
        }

        // Grouped rather than taking the worst rows and reducing them afterwards: one route that is off badly in
        // several places would otherwise fill the whole shortlist and hide every other route that shares the problem
        $dungeonRouteIds = $this->resolutionsQuery($dungeon, $mappingVersion, $npcIds, $minDistance)
            ->whereNotNull('dungeon_route_id')
            ->selectRaw('dungeon_route_id, MAX(weighted_distance) AS worst_distance')
            ->groupBy('dungeon_route_id')
            ->orderByDesc('worst_distance')
            ->limit(self::MATCHING_DUNGEON_ROUTES_LIMIT)
            ->pluck('dungeon_route_id')
            ->all();

        if (empty($dungeonRouteIds)) {
            return [];
        }

        /** @var array<int, int> $orderByDungeonRouteId worst first, which whereIn() does not preserve by itself */
        $orderByDungeonRouteId = array_flip($dungeonRouteIds);

        return DungeonRoute::with('dungeon')
            ->whereIn('id', $dungeonRouteIds)
            ->get()
            ->sortBy(static fn(DungeonRoute $route) => $orderByDungeonRouteId[$route->id])
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
