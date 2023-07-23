<?php

namespace App\Service\CombatLog\Logging;

use App\Logging\StructuredLogging;

class CreateRouteBodyDungeonRouteBuilderLogging extends StructuredLogging implements CreateRouteBodyDungeonRouteBuilderLoggingInterface
{
    /**
     * @return void
     */
    public function buildKillZonesCreateNewActivePull(): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    /**
     * @param float $activePullAverageHPPercent
     * @param int   $chainPullDetectionHPPercent
     * @return void
     */
    public function buildKillZonesCreateNewActiveChainPull(float $activePullAverageHPPercent, int $chainPullDetectionHPPercent): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    /**
     * @param string $uniqueUid
     * @param string $timestamp
     * @return void
     */
    public function buildKillZonesEnemyEngaged(string $uniqueUid, string $timestamp): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    /**
     * @param string $uniqueUid
     * @param string $timestamp
     * @return void
     */
    public function buildKillZonesEnemyKilled(string $uniqueUid, string $timestamp): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }


    /**
     * @param array $guids
     * @return void
     */
    public function buildKillZonesCreateNewFinalPull(array $guids): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }
}
