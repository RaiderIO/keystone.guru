<?php

namespace App\Jobs\Logging;

interface FetchCombatLogRunFanoutLoggingInterface
{
    public function handleStart(int $runId, int $combatLogVersion): void;

    public function handleDownloadNotAvailable(int $runId): void;

    public function handleDispatchingSegment(int $runId, int $segmentId, string $downloadUrl): void;

    public function handleEnd(int $runId): void;
}
