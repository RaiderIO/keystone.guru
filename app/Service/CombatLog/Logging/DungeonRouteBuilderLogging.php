<?php

namespace App\Service\CombatLog\Logging;

use App\Logging\StructuredLogging;
use Exception;

class DungeonRouteBuilderLogging extends StructuredLogging implements DungeonRouteBuilderLoggingInterface
{
    /**
     * @param int $killZoneIndex
     *
     * @return void
     */
    public function createPullStart(int $killZoneIndex): void
    {
        $this->start(__METHOD__, get_defined_vars());
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
    public function createPullFindEnemyForGuidEnd(): void
    {
        $this->end(__METHOD__);
    }

    /**
     * @param int $enemyCount
     *
     * @return void
     */
    public function createPullInsertedEnemies(int $enemyCount): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    /**
     * @return void
     */
    public function createPullNoEnemiesPullDeleted(): void
    {
        $this->debug(__METHOD__);
    }


    /**
     * @param int $spellCount
     *
     * @return void
     */
    public function createPullSpellsAttachedToKillZone(int $spellCount): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }


    /**
     * @param int $npcId
     * @param int $targetNpcId
     *
     * @return void
     */
    public function findUnkilledEnemyForNpcAtIngameLocationMappingToDifferentNpcId(int $npcId, int $targetNpcId): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    /**
     * @return void
     */
    public function createPullEnd(): void
    {
        $this->end(__METHOD__);
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
     * @param int        $npcId
     * @param float      $ingameX
     * @param float      $ingameY
     * @param float|null $previousPullLat
     * @param float|null $previousPullLng
     * @param array      $preferredGroups
     *
     * @return void
     */
    public function findUnkilledEnemyForNpcAtIngameLocationStart(int $npcId, float $ingameX, float $ingameY, ?float $previousPullLat, ?float $previousPullLng, array $preferredGroups): void
    {
        $this->start(__METHOD__, get_defined_vars());
    }

    /**
     * @param int   $id
     * @param float $distanceBetweenEnemies
     * @param float $distanceBetweenLastPullAndEnemy
     * @param int   $group
     *
     * @return void
     */
    public function findUnkilledEnemyForNpcAtIngameLocationEnemyFoundInPreferredGroup(int $id, float $distanceBetweenEnemies, float $distanceBetweenLastPullAndEnemy, int $group): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    /**
     * @param int|null $enemyId
     * @param float    $distanceBetweenEnemies
     * @param float    $distanceBetweenLastPullAndEnemy
     *
     * @return void
     */
    public function findUnkilledEnemyForNpcAtIngameLocationClosestEnemy(?int $enemyId, float $distanceBetweenEnemies, float $distanceBetweenLastPullAndEnemy): void
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
     * @param float    $distanceBetweenEnemies
     * @param float    $distanceBetweenLastPullAndEnemy
     * @param int      $maxDistance
     *
     * @return void
     */
    public function findUnkilledEnemyForNpcAtIngameLocationEnemyTooFarAway(?int $enemyId, float $distanceBetweenEnemies, float $distanceBetweenLastPullAndEnemy, int $maxDistance): void
    {
        $this->warning(__METHOD__, get_defined_vars());
    }

    /**
     * @param int   $enemyId
     * @param float $distanceBetweenEnemies
     * @param float $distanceBetweenLastPullAndEnemy
     *
     * @return void
     */
    public function findUnkilledEnemyForNpcAtIngameLocationEnemyFound(int $enemyId, float $distanceBetweenEnemies, float $distanceBetweenLastPullAndEnemy): void
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

    /**
     * @param int  $enemiesCount
     * @param bool $considerPatrols
     *
     * @return void
     */
    public function findClosestEnemyAndDistanceFromList(int $enemiesCount, bool $considerPatrols): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    /**
     * @param int $killPriority
     * @param int $enemyCount
     *
     * @return void
     */
    public function findClosestEnemyAndDistanceFromListPriority(int $killPriority, int $enemyCount): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    /**
     * @return void
     */
    public function findClosestEnemyAndDistanceFromListFoundEnemy(): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    /**
     * @param int|null $enemyId
     * @param float    $distanceBetweenEnemies
     * @param float    $distanceBetweenLastPullAndEnemy
     *
     * @return void
     */
    public function findClosestEnemyAndDistanceFromListResult(?int $enemyId, float $distanceBetweenEnemies, float $distanceBetweenLastPullAndEnemy): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    /**
     * @param array $enemyXY
     * @param array $targetEnemyXY
     * @param float $distanceBetweenEnemies
     * @param float $closestEnemyDistanceBetweenEnemies
     *
     * @return void
     */
    public function findClosestEnemyAndDistanceDistanceBetweenEnemies(array $enemyXY, array $targetEnemyXY, float $distanceBetweenEnemies, float $closestEnemyDistanceBetweenEnemies): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }
}
