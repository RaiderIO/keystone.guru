<?php

namespace App\Service\CombatLog\Logging;

use App\Logging\StructuredLogging;
use Exception;

class DungeonRouteBuilderLogging extends StructuredLogging implements DungeonRouteBuilderLoggingInterface
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
     * @param Exception $exception
     * @param int       $uitMapId
     *
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
     * @param string $guid
     *
     * @return void
     */
    public function createPullFindEnemyForGuidStart(string $guid): void
    {
        $this->start(__METHOD__, get_defined_vars());
    }

    /**
     * @param int   $npcId
     * @param float $ingameX
     * @param float $ingameY
     *
     * @return void
     */
    public function createPullEnemyNotFound(int $npcId, float $ingameX, float $ingameY): void
    {
        $this->warning(__METHOD__, get_defined_vars());
    }
    
    /**
     * @return void
     */
    public function createPullFindEnemyForGuidEnd(): void
    {
        $this->end(__METHOD__);
    }

    /**
     * @param int   $npcId
     * @param float $ingameX
     * @param float $ingameY
     *
     * @return void
     */
    public function createPullEnemyAttachedToKillZone(int $npcId, float $ingameX, float $ingameY): void
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
     * @param int   $npcId
     * @param float $ingameX
     * @param float $ingameY
     *
     * @return void
     */
    public function findUnkilledEnemyForNpcAtIngameLocationStart(int $npcId, float $ingameX, float $ingameY): void
    {
        $this->start(__METHOD__, get_defined_vars());
    }
    
    /**
     * @param int $id
     * @param int $closestEnemyDistance
     * @param int $group
     *
     * @return void
     */
    public function findUnkilledEnemyForNpcAtIngameLocationEnemyFoundInPreferredGroup(int $id, int $closestEnemyDistance, int $group): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    /**
     * @param int|null $enemyId
     * @param float    $closestEnemyDistance
     *
     * @return void
     */
    public function findUnkilledEnemyForNpcAtIngameLocationClosestEnemy(?int $enemyId, float $closestEnemyDistance): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    /**
     * @return void
     */
    public function findUnkilledEnemyForNpcAtIngameLocationConsideringPatrols(): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    /**
     * @return void
     */
    public function findUnkilledEnemyForNpcAtIngameLocationEnemyIsBossIgnoringTooFarAwayCheck(): void
    {
        $this->info(__METHOD__, get_defined_vars());
    }

    /**
     * @param int|null $enemyId
     * @param float    $closestEnemyDistance
     * @param int      $maxDistance
     *
     * @return void
     */
    public function findUnkilledEnemyForNpcAtIngameLocationEnemyTooFarAway(?int $enemyId, float $closestEnemyDistance, int $maxDistance): void
    {
        $this->warning(__METHOD__, get_defined_vars());
    }

    /**
     * @param int   $enemyId
     * @param float $closestEnemyDistance
     *
     * @return void
     */
    public function findUnkilledEnemyForNpcAtIngameLocationEnemyFound(int $enemyId, float $closestEnemyDistance): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    /**
     * @return void
     */
    public function findUnkilledEnemyForNpcAtIngameLocationEnd(): void
    {
        $this->end(__METHOD__);
    }
}
