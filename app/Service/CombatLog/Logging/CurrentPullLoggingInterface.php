<?php

namespace App\Service\CombatLog\Logging;

interface CurrentPullLoggingInterface
{

    public function parseChallengeModeStarted(): void;
    public function parseChallengeModeEnded(): void;
    public function parseUnitDied(string $destGuid): void;
    public function parseUnitInCurrentPullKilled(string $guid): void;
    public function parseUnitEvadedRemovedFromCurrentPull(string $guid): void;
    public function parseUnitAddedToCurrentPull(string $newEnemyGuid): void;
}