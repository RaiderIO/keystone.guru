<?php

namespace App\Service\CombatLog;

use App\Models\CombatLog\CombatLogRouteEnemyFailure;
use App\Models\Dungeon;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Mapping\MappingVersion;
use App\Models\Npc\NpcEnemyForces;
use App\Service\CombatLog\Dtos\CombatLogRouteEnemyFailureHeatmapResult;
use App\Service\Coordinates\CoordinatesService;
use App\Service\Coordinates\CoordinatesServiceInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

readonly class CombatLogRouteEnemyFailureService implements CombatLogRouteEnemyFailureServiceInterface
{
    public function __construct(private CoordinatesServiceInterface $coordinatesService)
    {
    }

    /**
     * @param int[]|null $npcIds
     */
    public function getEnemyFailureHeatmapData(
        Dungeon        $dungeon,
        MappingVersion $mappingVersion,
        ?array         $npcIds,
    ): CombatLogRouteEnemyFailureHeatmapResult {
        $gridSizeX = (int)config('keystoneguru.heatmap.service.data.player.size_x');
        $gridSizeY = (int)config('keystoneguru.heatmap.service.data.player.size_y');

        $query = $this->failuresQuery($dungeon, $mappingVersion);

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

        $dungeonRoutes = $this->getMatchingDungeonRoutes($dungeon, $mappingVersion, $npcIds);

        return new CombatLogRouteEnemyFailureHeatmapResult(
            $this->coordinatesService,
            $dungeon,
            $mappingVersion,
            $dataPerFloor,
            $gridSizeX,
            $gridSizeY,
            $totalCount,
            $dungeonRoutes,
        );
    }

    public function getFailureCountsPerNpc(Dungeon $dungeon, MappingVersion $mappingVersion): Collection
    {
        /** @var Collection<int, int> $result */
        $result = $this->failuresQuery($dungeon, $mappingVersion)
            ->whereNotNull('npc_id')
            ->selectRaw('npc_id, COUNT(*) AS failure_count')
            ->groupBy('npc_id')
            ->orderByDesc('failure_count')
            ->pluck('failure_count', 'npc_id')
            ->map(static fn($count): int => (int)$count);

        return $result;
    }

    public function getFailureCountsPerMappingVersion(Dungeon $dungeon): Collection
    {
        /** @var Collection<int, int> $counts */
        $counts = CombatLogRouteEnemyFailure::query()
            ->where('dungeon_id', $dungeon->id)
            ->selectRaw('mapping_version_id, COUNT(*) AS failure_count')
            ->groupBy('mapping_version_id')
            ->pluck('failure_count', 'mapping_version_id')
            ->map(static fn($count): int => (int)$count);

        /** @var Collection<int, int> $result */
        $result = $dungeon->mappingVersions
            ->mapWithKeys(static fn(MappingVersion $mappingVersion) => [$mappingVersion->id => $counts->get($mappingVersion->id, 0)]);

        return $result;
    }

    public function getZeroEnemyForcesNpcIds(MappingVersion $mappingVersion): array
    {
        return NpcEnemyForces::query()
            ->where('mapping_version_id', $mappingVersion->id)
            ->where('enemy_forces', 0)
            ->pluck('npc_id')
            ->map(static fn($npcId): int => (int)$npcId)
            ->all();
    }

    /**
     * The failures of the dungeon in the mapping version, minus those for npcs that are worth 0 enemy forces there.
     *
     * @return Builder<CombatLogRouteEnemyFailure>
     */
    private function failuresQuery(Dungeon $dungeon, MappingVersion $mappingVersion): Builder
    {
        $query = CombatLogRouteEnemyFailure::query()
            ->where('dungeon_id', $dungeon->id)
            ->where('mapping_version_id', $mappingVersion->id);

        $zeroEnemyForcesNpcIds = $this->getZeroEnemyForcesNpcIds($mappingVersion);
        if (!empty($zeroEnemyForcesNpcIds)) {
            // npc_id may be null - NOT IN would drop those rows, so keep them explicitly
            $query->where(static function (Builder $builder) use ($zeroEnemyForcesNpcIds) {
                $builder->whereNull('npc_id')
                    ->orWhereNotIn('npc_id', $zeroEnemyForcesNpcIds);
            });
        }

        return $query;
    }

    /**
     * @param  int[]|null                                                        $npcIds
     * @return array<int, array{public_key: string, title: string, url: string}>
     */
    private function getMatchingDungeonRoutes(Dungeon $dungeon, MappingVersion $mappingVersion, ?array $npcIds): array
    {
        if (empty($npcIds)) {
            return [];
        }

        $dungeonRouteIds = $this->failuresQuery($dungeon, $mappingVersion)
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
