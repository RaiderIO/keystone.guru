<?php

namespace App\Service\LiveSession\Logging;

use App\Logging\StructuredLogging;
use Throwable;

class LiveSessionBufferProcessingServiceLogging extends StructuredLogging implements LiveSessionBufferProcessingServiceLoggingInterface
{
    public function processBufferStart(int $id, string $publicKey): void
    {
        $this->start(__METHOD__, get_defined_vars());
    }

    public function processBufferNoPendingBatches(): void
    {
        $this->debug(__METHOD__);
    }

    public function processBufferPendingBatches(int $count, ?int $highestSequence): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    public function processBufferUnableToWrite(): void
    {
        $this->error(__METHOD__);
    }

    public function processBufferCombatSighting(string $eventTimestamp, string $guid, int $id): void
    {
        $this->info(__METHOD__, get_defined_vars());
    }

    public function processBufferEnd(): void
    {
        $this->end(__METHOD__, get_defined_vars());
    }

    public function writeCombatLogUnableToDecompressCheckpoint(): void
    {
        $this->error(__METHOD__);
    }

    public function writeCombatLogUnableToDecompressBatch(int $batchId): void
    {
        $this->error(__METHOD__, get_defined_vars());
    }

    public function saveCheckpointUnableToCompress(): void
    {
        $this->error(__METHOD__);
    }

    public function processPlayerPositionsNoLastKnownPlayerPositions(): void
    {
        $this->debug(__METHOD__);
    }

    public function processPlayerPositionsBroadcastPlayerMovedEvent(
        int    $id,
        string $playerGuid,
        string $characterName,
    ): void {
        $this->debug(__METHOD__, get_defined_vars());
    }

    public function processEnemiesBuildFailed(Throwable $throwable): void
    {
        $this->error(__METHOD__, get_defined_vars());
    }

    public function filterRecentlySeenEnemiesStart(int $count, ?string $newestEventTimestamp): void
    {
        $this->start(__METHOD__, get_defined_vars());
    }

    public function filterRecentlySeenEnemiesNewestEventTimestampNull(): void
    {
        $this->warning(__METHOD__);
    }

    public function filterRecentlySeenEnemiesTimedOut(string $guid, ?string $lastSeenAt): void
    {
        $this->info(__METHOD__, get_defined_vars());
    }

    public function filterRecentlySeenEnemiesEnd(int $count): void
    {
        $this->end(__METHOD__, get_defined_vars());
    }
}
