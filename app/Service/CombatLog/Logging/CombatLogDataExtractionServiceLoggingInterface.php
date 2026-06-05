<?php

namespace App\Service\CombatLog\Logging;

use App\Logging\StructuredLoggingInterface;
use Exception;

interface CombatLogDataExtractionServiceLoggingInterface extends StructuredLoggingInterface
{
    public function extractDataTimestampNotSet(): void;

    public function extractDataDungeonNotSet(): void;

    public function extractDataSetDungeon(string $dungeonName, ?int $keyLevel, ?string $affixGroup): void;

    public function extractDataSetZoneFailedChallengeModeActive(): void;

    public function extractDataZoneChangeDungeonNotFound(int $zoneId, string $zoneName): void;

    public function extractDataZoneChangeSetZone(string $dungeonName): void;

    public function extractSpellAuraIdsDungeonNotSet(): void;

    public function extractSpellAuraIdsFoundSpellId(int $spellId): void;

    public function extractDataAsyncStart(string $filePath, int $id): void;

    public function extractDataAsyncVerifying(): void;

    public function extractDataAsyncVerifyError(Exception $e): void;

    public function extractDataAsyncProcessing(): void;

    public function extractDataAsyncAnalyzeProgress(int $progressPercent, int $lineNr, int $totalLines): void;

    public function extractDataAsyncAnalyzeError(Exception $e): void;

    public function extractDataAsyncCompleted(): void;

    public function extractDataAsyncEnd(): void;
}
