<?php

namespace App\Service\WalkGraph\Logging;

use App\Logging\Concerns\InteractsWithRollbar;
use App\Logging\StructuredLogging;

class WalkGraphServiceLogging extends StructuredLogging implements WalkGraphServiceLoggingInterface
{
    use InteractsWithRollbar;

    public function buildGraphNoRuns(int $uiMapId): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    public function buildGraphBuilt(int $uiMapId, int $runCount, int $nodeCount, int $edgeCount, int $largestComponentNodeCount, int $prunedContributionCount): void
    {
        $this->info(__METHOD__, get_defined_vars());
    }

    public function calculatePackWalkDistancesTooFewRuns(int $mappingVersionId, int $floorId, ?int $runCount, int $minRuns): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    public function calculatePackWalkDistancesCalculated(int $mappingVersionId, int $floorId, int $packCount, int $snappedPackCount, int $pairCount, int $walkedPairCount): void
    {
        $this->info(__METHOD__, get_defined_vars());
    }
}
