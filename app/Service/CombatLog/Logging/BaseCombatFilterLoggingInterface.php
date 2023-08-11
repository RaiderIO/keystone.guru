<?php

namespace App\Service\CombatLog\Logging;

interface BaseCombatFilterLoggingInterface
{
    public function parseUnitDied(int $lineNr, string $guid): void;

    public function parseUnitDiedEnemyWasNotPartOfCurrentPull(int $lineNr, string $guid): void;

    public function parseUnitDiedEnemyWasAlreadyKilled(int $lineNr, string $guid): void;

    public function parseUnitDiedEnemyWasSummoned(int $lineNr, string $guid): void;

    public function parseUnitDiedInvalidNpcId(int $lineNr, string $guid): void;

    public function parseUnitDiedEnemyWasNotEngaged(int $lineNr, string $guid): void;

    public function parseUnitInCurrentPullKilled(int $lineNr, string $guid): void;

    public function parseUnitFirstSighted(int $lineNr, string $guid): void;

    public function parseUnitSummonedInWhitelist(int $lineNr, string $guid): void;

    public function parseUnitSummoned(int $lineNr, string $guid): void;

    public function parseUnitEvadedRemovedFromCurrentPull(int $lineNr, string $guid): void;

    public function parseUnitAddedToCurrentPull(int $lineNr, string $newEnemyGuid): void;

    public function getEnemyEngagedEventUsingFirstSightedEvent(string $guid): void;

    public function getEnemyEngagedEventUsingEngagedEvent(string $guid): void;
}
