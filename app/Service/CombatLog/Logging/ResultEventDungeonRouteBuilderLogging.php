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
    public function buildInCombatWithEnemy(string $guid): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    /**
     * @param string $guid
     *
     * @return void
     */
    public function buildUnitDiedNoLongerInCombat(string $guid): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    /**
     * @param string $guid
     *
     * @return void
     */
    public function buildUnitDiedNotInCombat(string $guid): void
    {
        $this->warning(__METHOD__, get_defined_vars());
    }

    /**
     * @param array $keys
     *
     * @return void
     */
    public function buildCreateNewPull(array $keys): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    /**
     * @param array $keys
     *
     * @return void
     */
    public function buildCreateNewFinalPull(array $keys): void
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
}
