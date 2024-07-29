<?php

namespace App\Service\CombatLog\DataExtractors\Logging;

use App\Logging\RollbarStructuredLogging;

class NpcUpdateDataExtractorLogging extends RollbarStructuredLogging implements NpcUpdateDataExtractorLoggingInterface
{
    public function extractDataNpcNotFound(int $npcId): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    public function extractDataUpdatedNpc(int $baseHealth, int $newBaseHealth): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

}
