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
        // A run's segments not being available yet is an expected, recurring state (#3918) - the
        // underlying RaiderIOApiServiceLogging call already logs the specific reason at the
        // appropriate level (error for a genuinely malformed response, info for "not yet uploaded"),
        // so this is just an echo and shouldn't page Sentry on its own.
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
