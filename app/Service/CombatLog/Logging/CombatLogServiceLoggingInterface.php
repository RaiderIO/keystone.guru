<?php

namespace App\Service\CombatLog\Logging;

use Exception;

interface CombatLogServiceLoggingInterface
{
    public function parseCombatLogToEventsUnableToParseRawEvent(string $rawEvent): void;

    public function getResultEventsStart(string $combatLogFilePath): void;

    public function getResultEventsAdvancedLogNotEnabled(string $message): void;

    public function getResultEventsEnd(): void;

    public function extractCombatLogExtractingArchiveStart(): void;

    public function extractCombatLogInvalidZipFile(): void;

    public function extractCombatLogExtractedArchive(string $extractedFilePath): void;

    public function extractCombatLogExtractingArchiveEnd(): void;

    public function parseCombatLogParseEventsStart(): void;

    public function parseCombatLogParseEventsChangedCombatLogVersion(int $combatLogVersion): void;

    public function parseCombatLogParseEventsException(string $rawEvent, Exception $exception): void;

    public function parseCombatLogParseEventsEnd(): void;

    public function compressCombatLogCompressingArchiveStart(): void;

    public function compressCombatLogInvalidZipFile(): void;

    public function compressCombatLogCompressedArchive(string $targetFilePath): void;

    public function compressCombatLogCompressingArchiveEnd(): void;
}
