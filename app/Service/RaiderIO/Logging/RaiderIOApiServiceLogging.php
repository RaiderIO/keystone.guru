<?php

namespace App\Service\RaiderIO\Logging;

use App\Logging\Concerns\InteractsWithRollbar;
use App\Logging\StructuredLogging;

class RaiderIOApiServiceLogging extends StructuredLogging implements RaiderIOApiServiceLoggingInterface
{
    use InteractsWithRollbar;

    public function getHeatmapDataStart(string $url): void
    {
        $this->start(__METHOD__, get_defined_vars());
    }

    public function getHeatmapDataInvalidResponse(string $dungeonName, string $url, string $response): void
    {
        // @TODO temporarily disable logging of invalid responses, it's spamming the logs
        $this->debug(__METHOD__, get_defined_vars());
    }

    public function getHeatmapDataEnd(): void
    {
        $this->end(__METHOD__);
    }

    public function searchAdvancedRunsStart(string $url): void
    {
        $this->start(__METHOD__, get_defined_vars());
    }

    public function searchAdvancedRunsInvalidResponse(string $url, string $response): void
    {
        $this->error(__METHOD__, get_defined_vars());
    }

    public function searchAdvancedRunsEnd(int $count): void
    {
        $this->end(__METHOD__, get_defined_vars());
    }

    public function getCombatLogSegmentsForRunStart(int $runId): void
    {
        $this->start(__METHOD__, get_defined_vars());
    }

    public function getCombatLogSegmentsForRunInvalidResponse(int $runId, string $url, string $response): void
    {
        $this->error(__METHOD__, get_defined_vars());
    }

    public function getCombatLogSegmentsForRunEnd(int $runId): void
    {
        $this->end(__METHOD__, get_defined_vars());
    }
}
