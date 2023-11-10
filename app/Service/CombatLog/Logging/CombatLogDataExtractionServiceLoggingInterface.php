<?php

namespace App\Service\CombatLog\Logging;

interface CombatLogDataExtractionServiceLoggingInterface
{

    public function extractDataTimestampNotSet(): void;

    public function extractDataSetChallengeMode(string $dungeonName, int $keyLevel, string $affixGroup): void;

    public function extractDataSetZoneFailedChallengeModeActive(): void;

    public function extractDataSetZone(string $dungeonName): void;

    public function extractDataAddedNewFloorConnection(int $previousFloorId, int $currentFloorId);

    public function extractDataNpcNotFound(int $npcId): void;

    public function extractDataUpdatedNpc(int $baseHealth): void;
}
