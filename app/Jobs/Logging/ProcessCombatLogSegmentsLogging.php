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
        // RaiderIOApiServiceLogging already logged the specific reason, at warning or info depending
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
        // One run's segments failing to download costs nothing: the run is skipped, the parsing
        // budget it consumed is given back, and the next poll spends it elsewhere. Only the volume
        // of these matters, which combatlog:reportpollinghealth reports on hourly (#4173).
        $this->warning(__METHOD__, get_defined_vars());
    }

    public function handleSegmentIsNotACombatLog(int $runId, int $segmentId, string $tempPath): void
    {
        $this->warning(__METHOD__, get_defined_vars());
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
        // Players upload logs we cannot parse all the time, and there is nothing to do about any one
        // of them - the run is skipped and its budget given back. What is worth acting on is a lot of
        // them at once, which is what combatlog:reportpollinghealth reports at error level (#4173).
        // The full detail (exception class, line number, raw line) stays in the logs either way, so a
        // spike can still be traced back to the line that caused it.
        $this->warning(__METHOD__, get_defined_vars());
    }

    public function handleEnd(int $runId, bool $result): void
    {
        $this->end(__METHOD__, get_defined_vars());
    }
}
