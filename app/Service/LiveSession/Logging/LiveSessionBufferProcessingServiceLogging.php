<?php

namespace App\Service\LiveSession\Logging;

use App\Logging\StructuredLogging;

class LiveSessionBufferProcessingServiceLogging extends StructuredLogging implements LiveSessionBufferProcessingServiceLoggingInterface
{
    public function processBufferStart(int $id, string $publicKey): void
    {
        $this->start(__METHOD__, get_defined_vars());
    }

    public function processBufferBufferIsNull(): void
    {
        $this->warning(__METHOD__);
    }

    public function processBufferUnableToDecompress(): void
    {
        $this->error(__METHOD__);
    }

    public function processBufferUnableToWrite(): void
    {
        $this->error(__METHOD__);
    }

    public function processBufferCombatSighting(string $eventTimestamp, string $guid, int $id): void
    {
        $this->info(__METHOD__, get_defined_vars());
    }

    public function processBufferPreCombatRemoval(string $rawEvent): void
    {
        $this->info(__METHOD__, get_defined_vars());
    }

    public function processBufferCombatRemoval(string $eventTimestamp, string $guid): void
    {
        $this->info(__METHOD__, get_defined_vars());
    }

    public function processBufferEnd(): void
    {
        $this->end(__METHOD__, get_defined_vars());
    }

    public function reduceBufferUnableToCompress(): void
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

    public function resolveInCombatEnemiesStart(int $count, int $count1, ?string $newestEventTimestamp): void
    {
        $this->start(__METHOD__, get_defined_vars());
    }

    public function resolveInCombatEnemiesNewestEventTimestampNull(): void
    {
        $this->warning(__METHOD__);
    }

    public function resolveInCombatEnemiesTimedOut(array $sighting): void
    {
        $this->info(__METHOD__, get_defined_vars());
    }

    public function resolveInCombatEnemiesUnableToResolveEnemy(array $sighting): void
    {
        $this->warning(__METHOD__, get_defined_vars());
    }

    public function resolveInCombatEnemiesEnd(int $count): void
    {
        $this->end(__METHOD__, get_defined_vars());
    }
}
