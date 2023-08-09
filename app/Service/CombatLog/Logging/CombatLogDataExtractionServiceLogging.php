<?php

namespace App\Service\CombatLog\Logging;

use App\Logging\StructuredLogging;

class CombatLogDataExtractionServiceLogging extends StructuredLogging implements CombatLogDataExtractionServiceLoggingInterface
{
    /**
     * @return void
     */
    public function extractDataTimestampNotSet(): void
    {
        $this->warning(__METHOD__);
    }

    /**
     * @param string $dungeonName
     * @param int    $keyLevel
     * @param string $affixGroup
     *
     * @return void
     */
    public function extractDataSetChallengeMode(string $dungeonName, int $keyLevel, string $affixGroup): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    /**
     * @param string $dungeonName
     *
     * @return void
     */
    public function extractDataSetZone(string $dungeonName): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    /**
     * @param int $previousFloorId
     * @param int $currentFloorId
     *
     * @return void
     */
    public function extractDataAddedNewFloorConnection(int $previousFloorId, int $currentFloorId)
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    /**
     * @param int $npcId
     *
     * @return void
     */
    public function extractDataNpcNotFound(int $npcId): void
    {
        $this->notice(__METHOD__, get_defined_vars());
    }

    /**
     * @param int $baseHealth
     *
     * @return void
     */
    public function extractDataUpdatedNpc(int $baseHealth): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }
}
