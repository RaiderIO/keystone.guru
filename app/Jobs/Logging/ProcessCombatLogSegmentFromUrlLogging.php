<?php

namespace App\Jobs\Logging;

use App\Logging\StructuredLogging;

class ProcessCombatLogSegmentFromUrlLogging extends StructuredLogging implements ProcessCombatLogSegmentFromUrlLoggingInterface
{
    public function handleStart(int $runId, int $segmentId, string $downloadUrl, int $combatLogVersion): void
    {
        $this->start(__METHOD__, get_defined_vars());
    }

    public function handleDownloadFailed(int $runId, int $segmentId, string $tempPath): void
    {
        $this->error(__METHOD__, get_defined_vars());
    }

    public function handleDownloaded(string $tempPath): void
    {
        $this->info(__METHOD__, get_defined_vars());
    }

    public function handleParseError(int $runId, int $combatLogVersion, string $message, string $class): void
    {
        $this->error(__METHOD__, get_defined_vars());
    }

    public function handleEnd(int $runId, bool $result): void
    {
        $this->end(__METHOD__, get_defined_vars());
    }
}
