<?php

namespace App\Jobs\Logging;

use App\Logging\StructuredLogging;

class ProcessCombatLogSegmentsLogging extends StructuredLogging implements ProcessCombatLogSegmentsLoggingInterface
{
    public function handleStart(int $runId, int $combatLogVersion): void
    {
        $this->start(__METHOD__, get_defined_vars());
    }

    public function handleSegmentsNotAvailable(int $runId): void
    {
        // Fires for two distinct callers (#3918), both an expected, recurring state rather than a
        // broken integration: getCombatLogSegmentsForRun() returned null (in which case
        // RaiderIOApiServiceLogging already logged the specific reason, at error or info depending
        // on cause), or it returned a well-formed response whose segments array is simply empty (no
        // upstream log at all for that case - this is the only signal). Neither should page Sentry.
        $this->info(__METHOD__, get_defined_vars());
    }

    public function handleDownloadingSegment(int $runId, int $segmentId, string $downloadUrl, string $tempPath): void
    {
        $this->info(__METHOD__, get_defined_vars());
    }

    public function handleSegmentDownloadFailed(int $runId, int $segmentId, string $tempPath): void
    {
        $this->error(__METHOD__, get_defined_vars());
    }

    public function handleSegmentIsNotACombatLog(int $runId, int $segmentId, string $tempPath): void
    {
        $this->error(__METHOD__, get_defined_vars());
    }

    public function handleParseError(
        int     $runId,
        ?int    $seasonId,
        int     $combatLogVersion,
        ?int    $lineNumber,
        string  $exceptionClass,
        string  $message,
        ?string $rawLine,
    ): void {
        $this->error(__METHOD__, get_defined_vars());
    }

    public function handleEnd(int $runId, bool $result): void
    {
        $this->end(__METHOD__, get_defined_vars());
    }
}
