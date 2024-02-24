<?php

namespace App\Service\CombatLog\Logging;

interface CreateRouteBodyDungeonRouteBuilderLoggingInterface
{

    /**
     * @return void
     */
    public function buildKillZonesCreateNewActivePull(): void;

    /**
     * @return void
     */
    public function buildKillZonesCreateNewActivePullChainPullCompleted(): void;

    /**
     *
     * @return void
     */
    public function buildKillZonesCreateNewActiveChainPull(float $activePullAverageHPPercent, int $chainPullDetectionHPPercent): void;

    /**
     * @return void
     */
    public function buildKillZonesUnableToFindEnemyForNpc(string $uniqueUid): void;

    /**
     *
     * @return void
     */
    public function buildKillZonesEnemyEngaged(string $uniqueUid, string $timestamp): void;

    /**
     *
     * @return void
     */
    public function buildKillZonesEnemyKilled(string $uniqueUid, string $timestamp): void;

    /**
     * @return void
     */
    public function buildKillZonesCreateNewFinalPull(array $guids): void;
}
