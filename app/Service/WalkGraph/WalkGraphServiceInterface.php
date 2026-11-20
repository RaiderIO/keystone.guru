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
     * Replaces the floor's pack distances in the mapping version. A floor whose walk graph has too few runs gets
     * none at all, so consumers fall back to straight-line distances.
     *
     * @return int the number of pack pairs written
     */
    public function calculatePackWalkDistances(MappingVersion $mappingVersion, Floor $floor): int;
}
