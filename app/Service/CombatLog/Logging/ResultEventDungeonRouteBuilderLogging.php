<?php

namespace App\Service\CombatLog\Logging;

use App\Logging\StructuredLogging;

class ResultEventDungeonRouteBuilderLogging extends StructuredLogging implements ResultEventDungeonRouteBuilderLoggingInterface
{

    /**
     * @param string $toDateTimeString
     * @param string $eventName
     *
     * @return void
     */
    public function buildStart(string $toDateTimeString, string $eventName): void
    {
        $this->start(__METHOD__, get_defined_vars());
    }

    /**
     * @return void
     */
    public function buildNoFloorFoundYet(): void
    {
        $this->warning(__METHOD__, get_defined_vars());
    }

    /**
     * @return void
     */
    public function buildChallengeModeEnded(): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    /**
     * @param string $guid
     *
     * @return void
     */
    public function buildUnableToFindEnemyForNpc(string $guid): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    /**
     * @param string $guid
     *
     * @return void
     */
    public function buildInCombatWithEnemy(string $guid): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    /**
     * @param string $guid
     *
     * @return void
     */
    public function buildEnemyNotInValidNpcIds(string $guid): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    /**
     * @param string $guid
     * @param int    $spellId
     * @return void
     */
    public function buildSpellCast(string $guid, int $spellId): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }


    /**
     * @param array $guids
     *
     * @return void
     */
    public function buildCreateNewFinalPull(array $guids): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    /**
     * @return void
     */
    public function buildEnd(): void
    {
        $this->end(__METHOD__);
    }

    /**
     * @return void
     */
    public function buildCreateNewActivePull(): void
    {
        $this->debug(__METHOD__);
    }

    /**
     * @return void
     */
    public function buildCreateNewActivePullChainPullCompleted(): void
    {
        $this->debug(__METHOD__);
    }

    /**
     * @param float $activePullAverageHPPercent
     * @param int   $chainPullDetectionHPPercent
     * @return void
     */
    public function buildCreateNewActiveChainPull(float $activePullAverageHPPercent, int $chainPullDetectionHPPercent): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    /**
     * @param string $guid
     * @param string $timestamp
     * @return void
     */
    public function buildEnemyKilled(string $guid, string $timestamp): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }
}
