<?php

namespace App\Jobs\Logging;

interface ProcessCombatLogPartLoggingInterface
{
    public function handleStart(string $s3Bucket, string $s3FilePath, int $combatLogVersion): void;

    public function handleDownloaded(string $tempPath): void;

    public function handleFileWriteFailed(string $tempPath): void;

    public function handleParseError(int $combatLogVersion, string $offendingLine, string $reason, string $filePath): void;

    public function handleEnd(bool $result): void;
}
