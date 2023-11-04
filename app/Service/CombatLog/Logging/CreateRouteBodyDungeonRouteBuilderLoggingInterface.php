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
     * @param float $activePullAverageHPPercent
     * @param int   $chainPullDetectionHPPercent
     *
     * @return void
     */
    public function buildKillZonesCreateNewActiveChainPull(float $activePullAverageHPPercent, int $chainPullDetectionHPPercent): void;

    /**
     * @param string $uniqueUid
     *
     * @return void
     */
    public function buildKillZonesUnableToFindEnemyForNpc(string $uniqueUid): void;

    /**
     * @param string $uniqueUid
     * @param string $timestamp
     *
     * @return void
     */
    public function buildKillZonesEnemyEngaged(string $uniqueUid, string $timestamp): void;

    /**
     * @param string $uniqueUid
     * @param string $timestamp
     *
     * @return void
     */
    public function buildKillZonesEnemyKilled(string $uniqueUid, string $timestamp): void;

    /**
     * @param array $guids
     *
     * @return void
     */
    public function buildKillZonesCreateNewFinalPull(array $guids): void;
}
