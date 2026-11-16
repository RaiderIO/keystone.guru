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
     * dungeon's newest mapping versions on those ui maps.
     *
     * @return array{graphs: int, distances: int} the number of graphs built and pack distances written
     */
    public function buildAll(): array;

    /**
     * Rebuilds the ui map's walk graph from its most recent runs and prunes the contributions of older runs.
     *
     * @return WalkGraph|null Null when no run walked the ui map.
     */
    public function buildGraph(int $uiMapId): ?WalkGraph;

    public function getGridGraph(WalkGraph $walkGraph): WalkGridGraph;

    /**
     * Replaces the floor's pack distances in the mapping version. A floor whose walk graph has too few runs gets
     * none at all, so consumers fall back to straight-line distances.
     *
     * @return int the number of pack pairs written
     */
    public function calculatePackWalkDistances(MappingVersion $mappingVersion, Floor $floor): int;
}
