<?php

namespace App\Service\CombatLog\Logging;

use App\Logging\StructuredLogging;
use Exception;

class DungeonRouteBuilderLogging extends StructuredLogging implements DungeonRouteBuilderLoggingInterface
{

    /**
     * @param string $toDateTimeString
     * @param string $eventName
     * @return void
     */
    public function buildStart(string $toDateTimeString, string $eventName): void
    {
        $this->start(__METHOD__, get_defined_vars());
    }

    /**
     * @param Exception $exception
     * @param int $uitMapId
     * @return void
     */
    public function findFloorByUiMapIdNoFloorFound(Exception $exception, int $uitMapId): void
    {
        $this->critical(__METHOD__, get_defined_vars());
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
     * @return void
     */
    public function buildInCombatWithEnemy(string $guid): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    /**
     * @param string $guid
     * @return void
     */
    public function buildNoEnemyFoundToPutInCombat(string $guid): void
    {
        $this->warning(__METHOD__, get_defined_vars());
    }

    /**
     * @param string $guid
     * @return void
     */
    public function buildUnitDiedNoLongerInCombat(string $guid): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    /**
     * @param string $guid
     * @return void
     */
    public function buildUnitDiedNotInCombat(string $guid): void
    {
        $this->warning(__METHOD__, get_defined_vars());
    }

    /**
     * @param array $keys
     * @return void
     */
    public function buildCreateNewPull(array $keys): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    /**
     * @param array $keys
     * @return void
     */
    public function buildCreateNewFinalPull(array $keys): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    /**
     * @param int $npcId
     * @param float $ingameX
     * @param float $ingameY
     * @return void
     */
    public function buildEnemyNotFound(int $npcId, float $ingameX, float $ingameY): void
    {
        $this->warning(__METHOD__, get_defined_vars());
    }

    /**
     * @param int $npcId
     * @param float $ingameX
     * @param float $ingameY
     * @return void
     */
    public function buildEnemyAttachedToKillZone(int $npcId, float $ingameX, float $ingameY): void
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
