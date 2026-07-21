<?php

namespace App\Service\LiveSession\Logging;

use App\Logging\StructuredLoggingInterface;

interface LiveSessionBufferProcessingServiceLoggingInterface extends StructuredLoggingInterface
{
    public function processBufferStart(int $id, string $publicKey): void;

    public function processBufferBufferIsNull(): void;

    public function processBufferUnableToDecompress(): void;

    public function processBufferUnableToWrite(): void;

    public function processBufferCombatSighting(string $eventTimestamp, string $guid, int $id): void;

    public function processBufferPreCombatRemoval(string $rawEvent): void;

    public function processBufferCombatRemoval(string $eventTimestamp, string $guid): void;

    public function processBufferEnd(): void;

    public function reduceBufferUnableToCompress(): void;

    public function processPlayerPositionsNoLastKnownPlayerPositions(): void;

    public function processPlayerPositionsBroadcastPlayerMovedEvent(
        int    $id,
        string $playerGuid,
        string $characterName,
    ): void;

    public function resolveInCombatEnemiesStart(
        int     $count,
        int     $count1,
        ?string $newestEventTimestamp,
    ): void;

    public function resolveInCombatEnemiesNewestEventTimestampNull(): void;

    /**
     * @param array{npcId: int, x: float, y: float, uiMapId: int, timestamp: \Carbon\Carbon} $sighting
     */
    public function resolveInCombatEnemiesTimedOut(array $sighting): void;

    /**
     * @param array{npcId: int, x: float, y: float, uiMapId: int, timestamp: \Carbon\Carbon} $sighting
     */
    public function resolveInCombatEnemiesUnableToResolveEnemy(array $sighting): void;

    public function resolveInCombatEnemiesEnd(int $count): void;
}
