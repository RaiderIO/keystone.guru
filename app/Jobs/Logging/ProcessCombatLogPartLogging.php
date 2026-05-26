<?php

namespace App\Jobs\Logging;

use App\Logging\StructuredLogging;

class ProcessCombatLogPartLogging extends StructuredLogging implements ProcessCombatLogPartLoggingInterface
{
    public function handleStart(string $s3Bucket, string $s3FilePath, int $combatLogVersion): void
    {
        $this->start(__METHOD__, get_defined_vars());
    }

    public function handleDownloaded(string $tempPath): void
    {
        $this->info(__METHOD__, get_defined_vars());
    }

    public function handleFileWriteFailed(string $tempPath): void
    {
        $this->error(__METHOD__, get_defined_vars());
    }

    public function handleParseError(int $combatLogVersion, string $offendingLine, string $reason, string $filePath): void
    {
        $this->error(__METHOD__, get_defined_vars());
    }

    public function handleEnd(bool $result): void
    {
        $this->end(__METHOD__, get_defined_vars());
    }
}
