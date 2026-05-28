<?php

namespace App\Jobs\Logging;

use App\Logging\StructuredLogging;

class ProcessCombatLogFanoutLogging extends StructuredLogging implements ProcessCombatLogFanoutLoggingInterface
{
    public function handleStart(string $s3Bucket, string $s3Path, int $combatLogVersion): void
    {
        $this->start(__METHOD__, get_defined_vars());
    }

    public function handleFileFound(string $filePath): void
    {
        $this->info(__METHOD__, get_defined_vars());
    }

    public function handleEnd(int $fileCount): void
    {
        $this->end(__METHOD__, get_defined_vars());
    }
}
