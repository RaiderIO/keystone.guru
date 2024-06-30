<?php

namespace App\Service\CombatLog\Builders\Logging;

class CreateRouteBodyDungeonRouteBuilderLogging extends DungeonRouteBuilderLogging implements CreateRouteBodyDungeonRouteBuilderLoggingInterface
{
    public function buildKillZonesNewCurrentFloor(int $floorId, int $uiMapId): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

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

    public function determineSpellsCastBetweenInvalidSpellIdBetween(int $spellId): void
    {
        $this->warning(__METHOD__, get_defined_vars());
    }

    public function determineSpellsCastBetweenInvalidSpellIdAfter(int $spellId): void
    {
        $this->warning(__METHOD__, get_defined_vars());
    }


}
