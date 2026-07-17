<?php

namespace App\Service\CombatLog\Builders\Logging;

use App\Logging\Concerns\InteractsWithRollbar;
use App\Logging\StructuredLogging;
use Exception;

class DungeonRouteBuilderLogging extends StructuredLogging implements DungeonRouteBuilderLoggingInterface
{
    use InteractsWithRollbar;

    public function createPullStart(int $killZoneIndex): void
    {
        $this->start(__METHOD__, get_defined_vars());
    }

    public function createPullFindEnemyForGuidStart(string $guid): void
    {
        $this->start(__METHOD__, get_defined_vars());
    }

    public function createPullEnemyNotFound(int $npcId, float $ingameX, float $ingameY): void
    {
        $this->warning(__METHOD__, get_defined_vars());
    }

    public function createPullEnemyAttachedToKillZone(int $npcId, float $ingameX, float $ingameY): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    public function createPullFindEnemyForGuidEnd(): void
    {
        $this->end(__METHOD__);
    }

    public function createPullInsertedEnemies(int $enemyCount): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    public function createPullNoEnemiesPullDeleted(): void
    {
        $this->debug(__METHOD__);
    }

    /**
     * @param array<int, int> $spellIds
     */
    public function createPullSpellsAttachedToKillZone(int $killZoneId, array $spellIds, int $spellCount): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    public function findUnkilledEnemyForNpcAtIngameLocationMappingToDifferentNpcId(int $npcId, int $targetNpcId): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    public function createPullEnd(): void
    {
        $this->end(__METHOD__);
    }

    public function findFloorByUiMapIdNoFloorFound(Exception $exception, int $uitMapId): void
    {
        $this->critical(__METHOD__, get_defined_vars());
    }

    /**
     * @param array<int, bool> $preferredGroups
     */
    public function findUnkilledEnemyForNpcAtIngameLocationStart(
        int    $npcId,
        float  $ingameX,
        float  $ingameY,
        ?float $previousPullLat,
        ?float $previousPullLng,
        array  $preferredGroups,
    ): void {
        $this->start(__METHOD__, get_defined_vars());
    }

    public function findUnkilledEnemyForNpcAtIngameLocationEnemyFoundInPreferredGroup(
        int   $id,
        float $distanceBetweenEnemies,
        float $distanceBetweenLastPullAndEnemy,
        int   $group,
    ): void {
        $this->debug(__METHOD__, get_defined_vars());
    }

    public function findUnkilledEnemyForNpcAtIngameLocationEnemyFoundInPreferredFloor(
        int   $id,
        float $distanceBetweenEnemies,
        float $distanceBetweenLastPullAndEnemy,
        int   $floorId,
    ): void {
        $this->debug(__METHOD__, get_defined_vars());
    }

    /**
     * @param array<int, bool> $preferredGroups
     */
    public function findClosestEnemyInPreferredGroupsStart(array $preferredGroups): void
    {
        $this->start(__METHOD__, get_defined_vars());
    }

    public function findClosestEnemyInPreferredGroupsEnd(): void
    {
        $this->end(__METHOD__);
    }

    public function findClosestEnemyInPreferredFloorStart(int $floorId): void
    {
        $this->start(__METHOD__, get_defined_vars());
    }

    public function findClosestEnemyInPreferredFloorEnd(): void
    {
        $this->end(__METHOD__);
    }

    public function findClosestEnemyInAllFilteredEnemiesStart(): void
    {
        $this->start(__METHOD__);
    }

    public function findClosestEnemyInAllFilteredEnemiesEnemyIsNull(
        float $distanceBetweenEnemies,
        float $distanceBetweenLastPullAndEnemy,
    ): void {
        $this->debug(__METHOD__, get_defined_vars());
    }

    public function findClosestEnemyInAllFilteredEnemiesEnemyIsBossIgnoringTooFarAwayCheck(): void
    {
        $this->info(__METHOD__, get_defined_vars());
    }

    public function findClosestEnemyInAllFilteredEnemiesEnemyTooFarAway(
        ?int  $enemyId,
        float $distanceBetweenEnemies,
        float $distanceBetweenLastPullAndEnemy,
        int   $maxDistance,
    ): void {
        $this->warning(__METHOD__, get_defined_vars());
    }

    public function findClosestEnemyInAllFilteredEnemiesEnd(): void
    {
        $this->end(__METHOD__);
    }

    public function findUnkilledEnemyForNpcAtIngameLocationEnemyFound(
        int   $enemyId,
        float $distanceBetweenEnemies,
        float $distanceBetweenLastPullAndEnemy,
    ): void {
        $this->debug(__METHOD__, get_defined_vars());
    }

    public function findUnkilledEnemyForNpcAtIngameLocationEnd(): void
    {
        $this->end(__METHOD__);
    }

    public function findClosestEnemyAndDistanceFromList(int $enemiesCount, bool $considerPatrols): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    public function findClosestEnemyAndDistanceFromListResult(
        ?int  $enemyId,
        float $distanceBetweenEnemies,
        float $distanceBetweenLastPullAndEnemy,
    ): void {
        $this->debug(__METHOD__, get_defined_vars());
    }

    /**
     * @param array<int, float> $enemyXY
     * @param array<int, float> $targetEnemyXY
     */
    public function findClosestEnemyAndDistanceDistanceBetweenEnemies(
        array $enemyXY,
        array $targetEnemyXY,
        float $distanceBetweenEnemies,
        float $closestEnemyDistanceBetweenEnemies,
    ): void {
        $this->debug(__METHOD__, get_defined_vars());
    }
}
