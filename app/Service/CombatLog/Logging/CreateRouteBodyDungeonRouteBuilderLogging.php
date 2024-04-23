<?php

namespace App\Service\CombatLog\Logging;

use App\Logging\RollbarStructuredLogging;

class CreateRouteBodyDungeonRouteBuilderLogging extends RollbarStructuredLogging implements CreateRouteBodyDungeonRouteBuilderLoggingInterface
{
    public function buildKillZonesCreateNewActivePull(): void
    {
        $this->debug(__METHOD__);
    }

    public function buildKillZonesCreateNewActivePullChainPullCompleted(): void
    {
        $this->debug(__METHOD__);
    }

    public function buildKillZonesCreateNewActiveChainPull(float $activePullAverageHPPercent, int $chainPullDetectionHPPercent): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    public function buildKillZonesUnableToFindEnemyForNpc(string $uniqueUid): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    public function buildKillZonesEnemyEngaged(string $uniqueUid, string $timestamp): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    public function buildKillZonesEnemyKilled(string $uniqueUid, string $timestamp): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    public function buildKillZonesCreateNewFinalPull(array $guids): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }
}
