<?php

namespace App\Service\CombatLog\Logging;

interface CombatLogServiceLoggingInterface
{

    public function parseCombatLogToEventsUnableToParseRawEvent(string $rawEvent): void;
    public function extractCombatLogExtractingArchive(): void;
    public function extractCombatLogInvalidZipFile(): void;
    public function extractCombatLogExtractedArchive(string $extractedFilePath): void;
    public function parseCombatLogParseEventsStart(): void;
    public function parseCombatLogParseEventsEnd(): void;
}
