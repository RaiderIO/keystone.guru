<?php

namespace App\Service\CombatLog\Logging;

interface CreateRouteBodyDungeonRouteBuilderLoggingInterface extends DungeonRouteBuilderLoggingInterface
{
    public function buildKillZonesCreateNewActivePull(): void;

    public function buildKillZonesCreateNewActivePullChainPullCompleted(): void;

    public function buildKillZonesCreateNewActiveChainPull(float $activePullAverageHPPercent, int $chainPullDetectionHPPercent): void;

    public function buildKillZonesUnableToFindEnemyForNpc(string $uniqueUid): void;

    public function buildKillZonesEnemyEngaged(string $uniqueUid, string $timestamp): void;

    public function buildKillZonesEnemyKilled(string $uniqueUid, string $timestamp): void;

    public function buildKillZonesCreateNewFinalPull(array $guids): void;

    public function determineSpellsCastBetweenInvalidSpellIdBetween(int $spellId): void;

    public function determineSpellsCastBetweenInvalidSpellIdAfter(int $spellId): void;
}
