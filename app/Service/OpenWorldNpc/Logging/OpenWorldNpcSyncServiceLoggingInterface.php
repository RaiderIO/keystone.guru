<?php

namespace App\Service\OpenWorldNpc\Logging;

use Closure;
use Throwable;

interface OpenWorldNpcSyncServiceLoggingInterface
{
    /**
     * Wraps $callback in a syncStart/End pair, guaranteeing balanced logs even when the callback throws.
     *
     * @template T
     *
     * @param Closure(): T $callback
     *
     * @return T
     */
    public function sync(string $diskName, string $filePath, Closure $callback): mixed;

    public function syncFileNotFound(): void;

    public function syncInvalidFile(string $message): void;

    public function syncInvalidEntries(int $invalidEntryCount, int $entryCount): void;

    public function syncEmptyFile(): void;

    public function syncNpcRenamed(int $npcId, string $oldName, string $newName): void;

    public function syncResult(int $created, int $renamed, int $unchanged, int $skipped, int $displayIdsFilled, int $withoutPortrait): void;

    public function fillDisplayIdsNotFound(int $npcId): void;

    public function fillDisplayIdsException(int $npcId, Throwable $throwable): void;
}
