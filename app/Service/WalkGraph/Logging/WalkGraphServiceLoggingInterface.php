<?php

namespace App\Service\WalkGraph\Logging;

interface WalkGraphServiceLoggingInterface
{
    public function buildGraphNoRuns(int $uiMapId): void;

    public function buildGraphKeptSeededGraph(int $uiMapId, int $runCount, int $seededRunCount, int $minRuns, int $prunedContributionCount): void;

    public function buildGraphBuilt(int $uiMapId, int $runCount, int $nodeCount, int $edgeCount, int $largestComponentNodeCount, int $prunedContributionCount): void;

    public function calculatePackWalkDistancesTooFewRuns(int $mappingVersionId, int $floorId, ?int $runCount, int $minRuns): void;

    public function calculatePackWalkDistancesCalculated(
        int $mappingVersionId,
        int $floorId,
        int $packCount,
        int $snappedPackCount,
        int $pairCount,
        int $walkedPairCount,
        int $markerCount,
        int $snappedMarkerCount,
        int $packMarkerCount,
        int $walkedPackMarkerCount,
        int $markerPairCount,
        int $walkedMarkerPairCount,
    ): void;
}
