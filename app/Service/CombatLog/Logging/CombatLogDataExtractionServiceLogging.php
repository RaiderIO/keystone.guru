<?php

namespace App\Service\CombatLog\Logging;

use App\Logging\RollbarStructuredLogging;

class CombatLogDataExtractionServiceLogging extends RollbarStructuredLogging implements CombatLogDataExtractionServiceLoggingInterface
{
    public function extractDataTimestampNotSet(): void
    {
        $this->warning(__METHOD__);
    }

    public function extractDataDungeonNotSet(): void
    {
        $this->info(__METHOD__);
    }


    public function extractDataSetChallengeMode(string $dungeonName, int $keyLevel, string $affixGroup): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    public function extractDataSetZoneFailedChallengeModeActive(): void
    {
        $this->info(__METHOD__);
    }

    public function extractDataSetZone(string $dungeonName): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    public function extractDataAddedNewFloorConnection(int $previousFloorId, int $currentFloorId): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    public function extractDataNpcNotFound(int $npcId): void
    {
        $this->notice(__METHOD__, get_defined_vars());
    }

    public function extractDataUpdatedNpc(int $baseHealth): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }
}
