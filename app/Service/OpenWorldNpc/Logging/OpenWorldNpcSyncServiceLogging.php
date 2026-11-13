<?php

namespace App\Service\OpenWorldNpc\Logging;

use App\Logging\StructuredLogging;
use Throwable;

class OpenWorldNpcSyncServiceLogging extends StructuredLogging implements OpenWorldNpcSyncServiceLoggingInterface
{
    public function syncStart(string $diskName, string $filePath): void
    {
        $this->start(__METHOD__, get_defined_vars());
    }

    public function syncFileNotFound(): void
    {
        $this->error(__METHOD__);
    }

    public function syncInvalidFile(string $message): void
    {
        $this->error(__METHOD__, get_defined_vars());
    }

    public function syncInvalidEntries(int $invalidEntryCount, int $entryCount): void
    {
        $this->warning(__METHOD__, get_defined_vars());
    }

    public function syncEmptyFile(): void
    {
        $this->warning(__METHOD__);
    }

    public function syncNpcRenamed(int $npcId, string $oldName, string $newName): void
    {
        $this->info(__METHOD__, get_defined_vars());
    }

    public function syncResult(int $created, int $renamed, int $unchanged, int $skipped, int $displayIdsFilled, int $withoutPortrait): void
    {
        $this->info(__METHOD__, get_defined_vars());
    }

    public function fillDisplayIdsNotFound(int $npcId): void
    {
        $this->notice(__METHOD__, get_defined_vars());
    }

    public function fillDisplayIdsException(int $npcId, Throwable $throwable): void
    {
        $this->warning(__METHOD__, get_defined_vars());
    }

    public function syncEnd(): void
    {
        $this->end(__METHOD__);
    }
}
