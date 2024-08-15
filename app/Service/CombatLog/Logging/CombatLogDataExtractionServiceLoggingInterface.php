<?php

namespace App\Service\CombatLog\Logging;

interface CombatLogDataExtractionServiceLoggingInterface
{
    public function extractDataTimestampNotSet(): void;

    public function extractDataDungeonNotSet(): void;

    public function extractDataSetChallengeMode(string $dungeonName, int $keyLevel, ?string $affixGroup): void;

    public function extractDataSetZoneFailedChallengeModeActive(): void;

    public function extractDataZoneChangeDungeonNotFound(int $zoneId, string $zoneName): void;

    public function extractDataZoneChangeSetZone(string $dungeonName): void;

    public function extractSpellAuraIdsDungeonNotSet(): void;

    public function extractSpellAuraIdsFoundSpellId(int $spellId): void;
}
