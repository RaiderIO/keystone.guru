<?php

namespace App\Jobs\Logging;

use App\Logging\StructuredLogging;

class FetchCombatLogRunFanoutLogging extends StructuredLogging implements FetchCombatLogRunFanoutLoggingInterface
{
    public function handleStart(int $runId, int $combatLogVersion): void
    {
        $this->start(__METHOD__, get_defined_vars());
    }

    public function handleDownloadNotAvailable(int $runId): void
    {
        $this->error(__METHOD__, get_defined_vars());
    }

    public function handleDispatchingSegment(int $runId, int $segmentId, string $downloadUrl): void
    {
        $this->info(__METHOD__, get_defined_vars());
    }

    public function handleEnd(int $runId): void
    {
        $this->end(__METHOD__, get_defined_vars());
    }
}
