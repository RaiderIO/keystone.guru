<?php

namespace App\Service\WalkGraph;

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
     *                                                                                      Each ring is a closed list of [lat, lng]. Drawn together with the even-odd fill rule, a ring inside another
     *                                                                                      one is a hole in it. Empty for a facade floor, a floor without a ui map or a ui map without a graph.
     */
    public function getFloorArea(Floor $floor): array;

    /**
     * Replaces the floor's pack distances in the mapping version. A floor whose walk graph has too few runs gets
     * none at all, so consumers fall back to straight-line distances.
     *
     * @return int the number of pack pairs written
     */
    public function calculatePackWalkDistances(MappingVersion $mappingVersion, Floor $floor): int;
}
