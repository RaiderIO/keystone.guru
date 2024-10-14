<?php

namespace App\Service\CombatLog\Logging;

use App\Logging\RollbarStructuredLogging;
use Exception;

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


    public function extractDataSetChallengeMode(string $dungeonName, int $keyLevel, ?string $affixGroup): void
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

    public function extractDataAsyncStart(string $filePath, int $id): void
    {
        $this->start(__METHOD__, get_defined_vars());
    }

    public function extractDataAsyncVerifying(): void
    {
        $this->debug(__METHOD__);
    }

    public function extractDataAsyncVerifyError(Exception $e): void
    {
        $this->error(__METHOD__, get_defined_vars());
    }

    public function extractDataAsyncProcessing(): void
    {
        $this->debug(__METHOD__);
    }

    public function extractDataAsyncAnalyzeProgress(int $progressPercent, int $lineNr, int $totalLines): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    public function extractDataAsyncAnalyzeError(Exception $e): void
    {
        $this->error(__METHOD__, get_defined_vars());
    }

    public function extractDataAsyncCompleted(): void
    {
        $this->debug(__METHOD__);
    }

    public function extractDataAsyncEnd(): void
    {
        $this->end(__METHOD__);
    }


}
