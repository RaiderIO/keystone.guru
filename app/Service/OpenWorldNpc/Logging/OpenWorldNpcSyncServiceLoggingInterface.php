<?php

namespace App\Service\OpenWorldNpc\Logging;

use Throwable;

interface OpenWorldNpcSyncServiceLoggingInterface
{
    public function syncStart(string $diskName, string $filePath): void;

    public function syncFileNotFound(): void;

    public function syncInvalidFile(string $message): void;

    public function syncInvalidEntries(int $invalidEntryCount, int $entryCount): void;

    public function syncEmptyFile(): void;

    public function syncNpcRenamed(int $npcId, string $oldName, string $newName): void;

    public function syncResult(int $created, int $renamed, int $unchanged, int $skipped, int $displayIdsFilled, int $withoutPortrait): void;

    public function fillDisplayIdsNotFound(int $npcId): void;

    public function fillDisplayIdsException(int $npcId, Throwable $throwable): void;

    public function syncEnd(): void;
}
