<?php

namespace App\Jobs\Logging;

interface ProcessCombatLogSegmentsLoggingInterface
{
    public function handleStart(int $runId, int $combatLogVersion): void;

    public function handleSegmentsNotAvailable(int $runId): void;

    public function handleDownloadingSegment(int $runId, int $segmentId, string $downloadUrl, string $tempPath): void;

    public function handleSegmentDownloadFailed(int $runId, int $segmentId, string $tempPath): void;

    public function handleSegmentIsNotACombatLog(int $runId, int $segmentId, string $tempPath): void;

    /**
     * The parameter names become the log context keys the error tracker fingerprints and tags on, so `exceptionClass`
     * and `message` must keep exactly those names - see {@see \App\Logging\Handlers\FingerprintsStructuredErrorsHandler}.
     */
    public function handleParseError(
        int     $runId,
        ?int    $seasonId,
        int     $combatLogVersion,
        ?int    $lineNumber,
        string  $exceptionClass,
        string  $message,
        ?string $rawLine,
    ): void;

    public function handleEnd(int $runId, bool $result): void;
}
