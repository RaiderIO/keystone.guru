<?php

namespace App\Jobs\Logging;

interface FetchCombatLogRunFanoutLoggingInterface
{
    public function handleStart(int $runId, int $combatLogVersion): void;

    public function handleDownloadNotAvailable(int $runId): void;

    public function handleDispatchingPart(int $runId, string $diskName, string $s3Path): void;

    public function handleIteratingFiles(int $runId, string $s3Bucket, string $s3Path, int $fileCount): void;

    public function handleEnd(int $runId): void;
}
