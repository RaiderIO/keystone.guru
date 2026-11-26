<?php

namespace App\Service\LiveSession\Logging;

use App\Logging\StructuredLoggingInterface;
use Throwable;

interface LiveSessionBufferProcessingServiceLoggingInterface extends StructuredLoggingInterface
{
    public function processBufferStart(int $id, string $publicKey): void;

    public function processBufferNoPendingBatches(): void;

    public function processBufferPendingBatches(int $count, ?int $highestSequence): void;

    public function processBufferNoConsumableBatches(?string $heldUntil): void;

    public function processBufferUnableToWrite(): void;

    public function processBufferCombatSighting(string $eventTimestamp, string $guid, int $id): void;

    public function processBufferEnd(): void;

    public function writeCombatLogUnableToDecompressCheckpoint(): void;

    public function writeCombatLogUnableToDecompressBatch(int $batchId): void;

    public function saveCheckpointUnableToCompress(): void;

    public function selectConsumableBatchesHeldBack(int $expectedSequence, int $nextSequence, int $heldCount, string $heldUntil): void;

    public function selectConsumableBatchesSkippedGap(int $expectedSequence, int $nextSequence, int $heldCount): void;

    public function processPlayerPositionsNoLastKnownPlayerPositions(): void;

    public function processPlayerPositionsSavedPlayerPosition(
        int    $id,
        string $playerGuid,
        string $characterName,
    ): void;

    public function processEnemiesBuildFailed(Throwable $throwable): void;

    public function filterRecentlySeenEnemiesStart(int $count, ?string $newestEventTimestamp): void;

    public function filterRecentlySeenEnemiesNewestEventTimestampNull(): void;

    public function filterRecentlySeenEnemiesTimedOut(string $guid, ?string $lastSeenAt): void;

    public function filterRecentlySeenEnemiesEnd(int $count): void;
}
