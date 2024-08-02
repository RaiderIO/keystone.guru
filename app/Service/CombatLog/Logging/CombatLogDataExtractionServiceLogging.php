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

    public function extractDataZoneChangeDungeonNotFound(int $zoneId, string $zoneName): void
    {
        $this->warning(__METHOD__, get_defined_vars());
    }

    public function extractDataZoneChangeSetZone(string $dungeonName): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    public function extractSpellAuraIdsDungeonNotSet(): void
    {
        $this->debug(__METHOD__);
    }

    public function extractSpellAuraIdsFoundSpellId(int $spellId): void
    {
        $this->info(__METHOD__, get_defined_vars());
    }


}
