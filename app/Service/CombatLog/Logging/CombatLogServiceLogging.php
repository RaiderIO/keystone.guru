<?php

namespace App\Service\CombatLog\Logging;

use App\Logging\RollbarStructuredLogging;
use Exception;
use Throwable;

class CombatLogServiceLogging extends RollbarStructuredLogging implements CombatLogServiceLoggingInterface
{
    public function parseCombatLogToEventsUnableToParseRawEvent(string $rawEvent): void
    {
        $this->warning(__METHOD__, get_defined_vars());
    }

    public function getResultEventsForChallengeModeStart(string $combatLogFilePath): void
    {
        $this->start(__METHOD__, get_defined_vars());
    }

    public function getResultEventsForChallengeModeFilterParseError(
        string    $rawEvent,
        int       $lineNr,
        Throwable $throwable
    ): void {
        $this->error(__METHOD__, get_defined_vars());
    }

    public function getResultEventsForChallengeModeAdvancedLogNotEnabled(string $message): void
    {
        $this->error(__METHOD__, get_defined_vars());
    }

    public function getResultEventsForChallengeModeEnd(): void
    {
        $this->end(__METHOD__);
    }

    public function getResultEventsForDungeonOrRaidStart(string $combatLogFilePath): void
    {
        $this->start(__METHOD__, get_defined_vars());
    }

    public function getResultEventsForDungeonOrRaidEnd(): void
    {
        $this->end(__METHOD__);
    }

    public function extractCombatLogExtractingArchiveStart(): void
    {
        $this->start(__METHOD__);
    }

    public function extractCombatLogInvalidZipFile(): void
    {
        $this->error(__METHOD__);
    }

    public function extractCombatLogExtractedArchive(string $extractedFilePath): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    public function extractCombatLogExtractingArchiveEnd(): void
    {
        $this->end(__METHOD__);
    }

    public function parseCombatLogParseEventsStart(): void
    {
        $this->start(__METHOD__);
    }

    public function parseCombatLogParseEventsChangedCombatLogVersion(
        int  $combatLogVersion,
        bool $advancedLoggingEnabled
    ): void {
        $this->debug(__METHOD__, get_defined_vars());
    }

    public function parseCombatLogParseEventsException(string $rawEvent, Exception $exception): void
    {
        $this->error(__METHOD__, get_defined_vars());
    }

    public function parseCombatLogParseEventsEnd(): void
    {
        $this->end(__METHOD__);
    }

    public function compressCombatLogCompressingArchiveStart(): void
    {
        $this->start(__METHOD__);
    }

    public function compressCombatLogInvalidZipFile(): void
    {
        $this->error(__METHOD__);
    }

    public function compressCombatLogCompressedArchive(string $targetFilePath): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    public function compressCombatLogCompressingArchiveEnd(): void
    {
        $this->end(__METHOD__);
    }
}
