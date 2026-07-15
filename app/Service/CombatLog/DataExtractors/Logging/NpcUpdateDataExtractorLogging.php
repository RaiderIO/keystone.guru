<?php

namespace App\Service\CombatLog\DataExtractors\Logging;

use App\Logging\Concerns\InteractsWithRollbar;
use App\Logging\StructuredLogging;

class NpcUpdateDataExtractorLogging extends StructuredLogging implements NpcUpdateDataExtractorLoggingInterface
{
    use InteractsWithRollbar;

    public function extractDataNpcNotFound(int $npcId): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    public function extractDataUpdatedNpc(int $npcId, int $baseHealth, int $newBaseHealth): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }
}
