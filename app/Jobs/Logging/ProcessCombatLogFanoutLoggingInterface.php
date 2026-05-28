<?php

namespace App\Jobs\Logging;

interface ProcessCombatLogFanoutLoggingInterface
{
    public function handleStart(string $s3Bucket, string $s3Path, int $combatLogVersion): void;

    public function handleFileFound(string $filePath): void;

    public function handleEnd(int $fileCount): void;
}
