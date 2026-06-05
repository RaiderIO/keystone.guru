<?php

namespace App\Jobs\Logging;

interface ProcessCombatLogSegmentFromUrlLoggingInterface
{
    public function handleStart(int $runId, int $segmentId, string $downloadUrl, int $combatLogVersion): void;

    public function handleDownloadFailed(int $runId, int $segmentId, string $tempPath): void;

    public function handleDownloaded(string $tempPath): void;

    public function handleParseError(int $runId, int $combatLogVersion, string $message, string $class): void;

    public function handleEnd(int $runId, bool $result): void;
}
