<?php

namespace App\Service\CombatLog\Builders\Logging;

class ResultEventDungeonRouteBuilderLogging extends DungeonRouteBuilderLogging implements ResultEventDungeonRouteBuilderLoggingInterface
{
    public function buildStart(string $toDateTimeString, string $eventName): void
    {
        $this->start(__METHOD__, get_defined_vars());
    }

    public function buildNoFloorFoundYet(): void
    {
        $this->warning(__METHOD__, get_defined_vars());
    }

    public function buildChallengeModeEnded(): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    public function buildUnableToFindEnemyForNpc(string $guid): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    public function buildInCombatWithEnemy(string $guid): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    public function buildEnemyNotInValidNpcIds(string $guid): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    public function buildSpellCast(string $guid, int $spellId): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    public function buildCreateNewFinalPull(array $guids): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    public function buildEnd(): void
    {
        $this->end(__METHOD__);
    }

    public function buildCreateNewActivePull(): void
    {
        $this->debug(__METHOD__);
    }

    public function buildCreateNewActivePullChainPullCompleted(): void
    {
        $this->debug(__METHOD__);
    }

    public function buildCreateNewActiveChainPull(
        float $activePullAverageHPPercent,
        int   $chainPullDetectionHPPercent
    ): void {
        $this->debug(__METHOD__, get_defined_vars());
    }

    public function buildEnemyKilled(string $guid, string $timestamp): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }
}
