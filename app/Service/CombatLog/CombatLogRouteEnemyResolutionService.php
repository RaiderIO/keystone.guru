<?php

namespace App\Service\CombatLog;

use App\Logic\Structs\LatLng;
use App\Models\CombatLog\CombatLogRouteEnemyResolution;
use App\Models\Dungeon;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Floor\Floor;
use App\Models\Mapping\MappingVersion;
use App\Models\Npc\Npc;
use App\Models\User;
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

    public function getResolutionLines(
        Dungeon        $dungeon,
        MappingVersion $mappingVersion,
        ?array         $npcIds,
        ?float         $minDistance,
        int            $limit,
    ): array {
        /** @var Collection<int, CombatLogRouteEnemyResolution> $resolutions */
        $resolutions = $this->resolutionsQuery($dungeon, $mappingVersion, $npcIds, $minDistance)
            ->orderByDesc('weighted_distance')
            ->limit($limit)
            ->get();

        if ($resolutions->isEmpty()) {
            return [];
        }

        /** @var Collection<int, Npc> $npcs */
        $npcs = Npc::query()->whereIn('id', $resolutions->pluck('npc_id')->filter()->unique())->get()->keyBy('id');
        // An imported row's dungeon_route_id belongs to the deployment it came from and means nothing here, so only
        // rows this environment recorded itself get a route link - the others carry their source and public key
        // instead, which is what identifies the route where it lives
        /** @var Collection<int, DungeonRoute> $dungeonRoutes */
        $dungeonRoutes = DungeonRoute::with('dungeon')
            ->whereIn('id', $resolutions->whereNull('source')->pluck('dungeon_route_id')->filter()->unique())
            ->get()
            ->keyBy('id');

        /** @var Collection<int, Floor> $floors */
        $floors = $dungeon->floors->keyBy('id');
        // The map itself falls back to the real floors when the mapping version has no facade of its own, whatever
        // the viewer's style says - re-homing the lines onto a facade floor the map is not showing would drop them
        $useFacade   = User::getCurrentUserMapFacadeStyle() === User::MAP_FACADE_STYLE_FACADE && $mappingVersion->facade_enabled;
        $facadeFloor = $useFacade ? $dungeon->floors->firstWhere('facade', true) : null;

        $lines = [];
        foreach ($resolutions as $resolution) {
            /** @var Floor|null $floor */
            $floor = $floors->get($resolution->floor_id);
            if ($floor === null) {
                continue;
            }

            $engagedAt = new LatLng((float)$resolution->lat, (float)$resolution->lng, $floor);
            $enemyAt   = new LatLng((float)$resolution->enemy_lat, (float)$resolution->enemy_lng, $floor);
            $floorId   = $resolution->floor_id;

            if ($facadeFloor instanceof Floor) {
                $engagedAt = $this->coordinatesService->convertMapLocationToFacadeMapLocation($mappingVersion, $engagedAt);
                $enemyAt   = $this->coordinatesService->convertMapLocationToFacadeMapLocation($mappingVersion, $enemyAt);
                $floorId   = $facadeFloor->id;
            }

            /** @var Npc|null $npc */
            $npc = $resolution->npc_id === null ? null : $npcs->get($resolution->npc_id);
            /** @var DungeonRoute|null $dungeonRoute */
            $dungeonRoute = $resolution->source === null && $resolution->dungeon_route_id !== null
                ? $dungeonRoutes->get($resolution->dungeon_route_id)
                : null;

            $lines[] = [
                'floor_id'                 => $floorId,
                'lat'                      => round($engagedAt->getLat(), 2),
                'lng'                      => round($engagedAt->getLng(), 2),
                'enemy_lat'                => round($enemyAt->getLat(), 2),
                'enemy_lng'                => round($enemyAt->getLng(), 2),
                'distance'                 => round((float)$resolution->distance, 1),
                'weighted_distance'        => round((float)$resolution->weighted_distance, 1),
                'npc_id'                   => $resolution->npc_id,
                'npc_name'                 => $npc === null ? null : __($npc->name, [], 'en_US'),
                'enemy_id'                 => $resolution->enemy_id,
                'source'                   => $resolution->source,
                'dungeon_route_id'         => $resolution->dungeon_route_id,
                'dungeon_route_public_key' => $dungeonRoute?->public_key,
                'dungeon_route_url'        => $dungeonRoute === null ? null : route('dungeonroute.view', [
                    'dungeon'      => $dungeonRoute->dungeon,
                    'dungeonroute' => $dungeonRoute,
                    'title'        => $dungeonRoute->getTitleSlug(),
                ]),
            ];
        }

        return $lines;
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

    public function getResolutionCountsPerDungeonRoute(Collection $dungeonRoutes): Collection
    {
        if ($dungeonRoutes->isEmpty()) {
            return collect();
        }

        /** @var Collection<int, int> $result */
        $result = CombatLogRouteEnemyResolution::query()
            ->whereIn('dungeon_route_id', $dungeonRoutes->keys()->all())
            ->whereNull('source')
            ->selectRaw('dungeon_route_id, COUNT(*) AS resolution_count')
            ->groupBy('dungeon_route_id')
            ->pluck('resolution_count', 'dungeon_route_id')
            ->map(static fn($count): int => (int)$count);

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
            // An imported row's route id belongs to the deployment it came from and names an unrelated route here
            ->whereNull('source')
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
