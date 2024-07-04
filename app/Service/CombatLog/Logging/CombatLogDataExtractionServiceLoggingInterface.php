<?php

namespace App\Service\CombatLog\Logging;

interface CombatLogDataExtractionServiceLoggingInterface
{
    public function extractDataTimestampNotSet(): void;

    public function extractDataDungeonNotSet(): void;

    public function extractDataSetChallengeMode(string $dungeonName, int $keyLevel, string $affixGroup): void;

    public function extractDataSetZoneFailedChallengeModeActive(): void;

    public function extractDataSetZone(string $dungeonName): void;
}
