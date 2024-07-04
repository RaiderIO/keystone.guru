<?php

namespace App\Service\CombatLog\DataExtractors\Logging;

interface NpcUpdateDataExtractorLoggingInterface
{

    public function extractDataNpcNotFound(int $npcId): void;

    public function extractDataUpdatedNpc(int $baseHealth, int $newBaseHealth): void;
}
