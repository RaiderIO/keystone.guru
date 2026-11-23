<?php

namespace App\Service\WalkGraph;

use App\Logic\Structs\IngameXY;
use App\Logic\WalkGraph\WalkGridGraph;
use App\Models\Floor\Floor;
use App\Models\Mapping\MappingVersion;
use App\Models\WalkGraph\WalkGraph;

interface WalkGraphServiceInterface
{
    /**
     * Rebuilds every ui map's walk graph from its most recent runs, then the pack walking distances of every
     * ui map that has a walk graph, see {@see self::calculateAllPackWalkDistances()}.
     *
     * @return array{graphs: int, distances: int} the number of graphs built and pack distances written
     */
    public function buildAll(): array;

    /**
     * Rebuilds the ui map's walk graph from its most recent runs and prunes the contributions of older runs. A seeded
     * graph is only replaced once the combat logs yield at least the minimum number of runs.
     *
     * @return WalkGraph|null Null when no run walked the ui map, or when its seeded graph was kept.
     */
    public function buildGraph(int $uiMapId): ?WalkGraph;

    /**
     * Recalculates the pack walking distances of every dungeon's newest mapping versions on every ui map that has a
     * walk graph, seeded or built.
     *
     * @return int the number of pack pairs written
     */
    public function calculateAllPackWalkDistances(): int;

    public function getGridGraph(WalkGraph $walkGraph): WalkGridGraph;

    /**
     * The ui map's walk graph ready for path finding, built once per graph build within a request or queued job.
     *
     * @return WalkGridGraph|null Null when the ui map has no graph, or one with fewer runs than the minimum.
     */
    public function getGridGraphForUiMapId(int $uiMapId): ?WalkGridGraph;

    /**
     * The walking distance and path between every pair of points on the floor that its walk graph connects. Each point
     * snaps onto the graph within the snap radius, and a walk longer than the maximum walk distance is not searched.
     * The distance includes both snap offsets and is never shorter than the straight line.
     *
     * @template TKey of array-key
     *
     * @param array<TKey, IngameXY> $points
     *
     * @return array<TKey, array<TKey, array{distance: float, path: list<IngameXY>}>> from => to => the walk, both ways
     *                                                                                round. A path starts at the from point, ends at the to point and is simplified to the points that shape it.
     *                                                                                Pairs the graph cannot connect are left out. Empty for a facade floor, a floor without a ui map or a ui
     *                                                                                map without a graph with enough runs.
     */
    public function getWalkPaths(Floor $floor, array $points): array;

    /**
     * The floor's walk graph as straight map-location segments for drawing. Edges with an end outside the floor's
     * in-game bounds are left out, since floors that share a ui map each cover part of it.
     *
     * @return array{run_count: int, min_runs: int, max_run_count: int, segments: list<array{int, float, float, float, float}>}
     *                                                                                                                          Each segment is [runCount, fromLat, fromLng, toLat, toLng]. Empty for a facade floor, a floor without a ui
     *                                                                                                                          map or a ui map without a graph.
     */
    public function getFloorSegments(Floor $floor): array;

    /**
     * The outline of the floor's walkable area, built from where its walk graph says players walked. Edges with an end
     * outside the floor's in-game bounds are left out, like {@see self::getFloorSegments()}.
     *
     * @return array{run_count: int, min_runs: int, rings: list<list<array{float, float}>>}
     *                                                                                      Each ring is a closed list of [lat, lng]. Drawn together with the non-zero fill rule, a ring inside another
     *                                                                                      one is a hole in it. Empty for a facade floor, a floor without a ui map or a ui map without a graph.
     */
    public function getFloorArea(Floor $floor): array;

    /**
     * The walkable area of every floor shown on the facade floor that has a walk graph, moved onto the facade through
     * the mapping version's floor unions. Floors without a ui map or a graph are left out.
     *
     * @return array{run_count: int, min_runs: int, rings: list<list<array{float, float}>>}
     *                                                                                      Each ring is a closed list of [lat, lng] on the facade. Drawn together with the non-zero fill rule, a ring inside
     *                                                                                      another one is a hole in it, and the areas of overlapping floors add up. The run count is the lowest of the
     *                                                                                      floors' graphs. Empty for a floor that is not a facade, a mapping version without the facade, or no graphed floors.
     */
    public function getFacadeFloorArea(MappingVersion $mappingVersion, Floor $facadeFloor): array;

    /**
     * Replaces the floor's walking distances in the mapping version: between its packs, from each pack to each of its
     * floor switch markers, and between its floor switch markers. A floor whose walk graph has too few runs gets none
     * at all, so consumers fall back to straight-line distances.
     *
     * @return int the number of pack pairs written
     */
    public function calculatePackWalkDistances(MappingVersion $mappingVersion, Floor $floor): int;
}
