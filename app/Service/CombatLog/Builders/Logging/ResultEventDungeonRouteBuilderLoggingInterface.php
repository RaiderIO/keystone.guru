<?php

namespace App\Service\CombatLog\Builders\Logging;

interface ResultEventDungeonRouteBuilderLoggingInterface extends DungeonRouteBuilderLoggingInterface
{
    public function buildStart(string $toDateTimeString, string $eventName): void;

    public function buildNoFloorFoundYet(): void;

    public function buildChallengeModeEnded(): void;

    public function buildUnableToFindEnemyForNpc(string $guid): void;

    public function buildInCombatWithEnemy(string $guid): void;

    public function buildEnemyNotInValidNpcIds(string $guid): void;

    public function buildSpellCast(string $guid, int $spellId): void;

    public function buildCreateNewFinalPull(array $guids): void;

    public function buildEnd(): void;

    public function buildCreateNewActivePull(): void;

    public function buildCreateNewActivePullChainPullCompleted(): void;

    public function buildCreateNewActiveChainPull(float $activePullAverageHPPercent, int $chainPullDetectionHPPercent): void;

    public function buildEnemyKilled(string $guid, string $timestamp): void;
}
