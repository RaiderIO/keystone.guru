<?php

namespace App\Jobs\Logging;

interface ProcessCombatLogSegmentsLoggingInterface
{
    public function handleStart(int $runId, int $combatLogVersion): void;

    public function handleSegmentsNotAvailable(int $runId): void;

    public function handleDownloadingSegment(int $runId, int $segmentId, string $downloadUrl, string $tempPath): void;

    public function handleSegmentDownloadFailed(int $runId, int $segmentId, string $tempPath): void;

    public function handleJoiningSegments(int $runId, int $segmentCount, string $combinedPath): void;

    public function handleParseError(int $runId, int $combatLogVersion, string $message, string $class): void;

    public function handleEnd(int $runId, bool $result): void;
}
