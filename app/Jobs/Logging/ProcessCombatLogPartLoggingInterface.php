<?php

namespace App\Jobs\Logging;

interface ProcessCombatLogPartLoggingInterface
{
    public function handleStart(string $s3Bucket, string $s3FilePath, int $combatLogVersion): void;

    public function handleDownloaded(string $tempPath): void;

    public function handleFileWriteFailed(string $tempPath): void;

    /**
     * The parameter names become the log context keys the error tracker fingerprints and tags on, so `exceptionClass`
     * and `message` must keep exactly those names - see {@see \App\Logging\Handlers\FingerprintsStructuredErrorsHandler}.
     *
     * They were previously `$offendingLine` and `$reason`, which did not describe what the only caller actually passes
     * (`$e->getMessage()` then `$e::class`), so every logged context had those two values under misleading keys.
     */
    public function handleParseError(
        int    $combatLogVersion,
        string $message,
        string $exceptionClass,
        string $filePath,
    ): void;

    public function handleEnd(bool $result): void;
}
