<?php

namespace App\Service\CombatLog\DataExtractors\Logging;

use App\Logging\RollbarStructuredLogging;

class NpcCharacteristicDataExtractorLogging extends RollbarStructuredLogging implements NpcCharacteristicDataExtractorLoggingInterface
{
    public function extractDataAssignedCharacteristicToNpc(int $npcId, string $characteristicKey, string $rawEvent): void
    {
        $this->info(__METHOD__, get_defined_vars());
    }

    public function extractDataNpcNotFound(int $npcId): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    public function extractDataCharacteristicAlreadyAssigned(int $npcId, string $characteristicKey): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }
}
