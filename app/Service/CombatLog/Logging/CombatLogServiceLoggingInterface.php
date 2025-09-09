<?php

namespace App\Service\CombatLog\Logging;

use Exception;
use Throwable;

interface CombatLogServiceLoggingInterface
{
    public function parseCombatLogToEventsUnableToParseRawEvent(string $rawEvent): void;

    public function getResultEventsForChallengeModeStart(string $combatLogFilePath): void;

    public function getResultEventsForChallengeModeFilterParseError(
        string    $rawEvent,
        int       $lineNr,
        Throwable $throwable
    ): void;

    public function getResultEventsForChallengeModeAdvancedLogNotEnabled(string $message): void;

    public function getResultEventsForChallengeModeEnd(): void;

    public function getResultEventsForDungeonOrRaidStart(string $combatLogFilePath): void;

    public function getResultEventsForDungeonOrRaidEnd(): void;

    public function extractCombatLogExtractingArchiveStart(): void;

    public function extractCombatLogInvalidZipFile(): void;

    public function extractCombatLogExtractedArchive(string $extractedFilePath): void;

    public function extractCombatLogExtractingArchiveEnd(): void;

    public function parseCombatLogParseEventsStart(): void;

    public function parseCombatLogParseEventsChangedCombatLogVersion(
        int  $combatLogVersion,
        bool $advancedLoggingEnabled
    ): void;

    public function parseCombatLogParseEventsException(string $rawEvent, Exception $exception): void;

    public function parseCombatLogParseEventsEnd(): void;

    public function compressCombatLogCompressingArchiveStart(): void;

    public function compressCombatLogInvalidZipFile(): void;

    public function compressCombatLogCompressedArchive(string $targetFilePath): void;

    public function compressCombatLogCompressingArchiveEnd(): void;
}
