<?php

namespace App\Service\CombatLog\Filters\Logging;

interface BaseCombatFilterLoggingInterface
{
    public function parseEncounterEndBossFoundAndKilled(int $lineNr, string $bossGuid): void;

    public function parseEncounterEndNpcNotInCombat(int $lineNr, int $npcId): void;

    public function parseEncounterEndNoNpc(int $lineNr): void;

    public function parseUnitDied(int $lineNr, string $guid): void;

    public function parseEnemyWasNotPartOfCurrentPull(int $lineNr, string $guid): void;

    public function parseEnemyWasAlreadyKilled(int $lineNr, string $guid): void;

    public function parseEnemyWasSummoned(int $lineNr, string $guid): void;

    public function parseInvalidNpcId(int $lineNr, string $guid): void;

    public function parseEnemyWasNotEngaged(int $lineNr, string $guid): void;

    public function parseUnitInCurrentPullKilled(int $lineNr, string $guid): void;

    public function parseUnitFirstSighted(int $lineNr, string $guid): void;

    public function parseUnitSummonedInWhitelist(int $lineNr, string $guid): void;

    public function parseUnitSummoned(int $lineNr, string $guid): void;

    public function parseUnitEvadedRemovedFromCurrentPull(int $lineNr, string $guid): void;

    public function parseUnitAddedToCurrentPull(int $lineNr, string $newEnemyGuid): void;

    public function getEnemyEngagedEventUsingFirstSightedEvent(string $guid): void;

    public function getEnemyEngagedEventUsingEngagedEvent(string $guid): void;


}
