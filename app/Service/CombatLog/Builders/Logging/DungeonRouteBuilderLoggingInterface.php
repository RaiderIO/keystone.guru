<?php

namespace App\Service\CombatLog\Builders\Logging;

use Exception;

interface DungeonRouteBuilderLoggingInterface
{
    public function createPullStart(int $killZoneIndex): void;

    public function createPullFindEnemyForGuidStart(string $guid): void;

    public function createPullEnemyNotFound(int $npcId, float $ingameX, float $ingameY): void;

    public function createPullEnemyAttachedToKillZone(int $npcId, float $ingameX, float $ingameY): void;

    public function createPullFindEnemyForGuidEnd(): void;

    public function createPullInsertedEnemies(int $enemyCount): void;

    public function createPullNoEnemiesPullDeleted(): void;

    /**
     * @param array<int, int> $spellIds
     */
    public function createPullSpellsAttachedToKillZone(int $killZoneId, array $spellIds, int $spellCount): void;

    public function findUnkilledEnemyForNpcAtIngameLocationMappingToDifferentNpcId(int $npcId, int $targetNpcId): void;

    public function createPullEnd(): void;

    public function findFloorByUiMapIdNoFloorFound(Exception $exception, int $uitMapId): void;

    /**
     * @param array<int, bool> $preferredGroups
     */
    public function findUnkilledEnemyForNpcAtIngameLocationStart(
        int   $npcId,
        float $ingameX,
        float $ingameY,
        array $preferredGroups,
    ): void;

    public function findUnkilledEnemyForNpcAtIngameLocationEnemyFoundInPreferredGroup(
        int   $id,
        float $distanceBetweenEnemies,
        int   $group,
    ): void;

    public function findUnkilledEnemyForNpcAtIngameLocationEnemyFoundInPreferredFloor(
        int   $id,
        float $distanceBetweenEnemies,
        int   $floorId,
    ): void;

    public function findUnkilledEnemyForNpcAtIngameLocationEnemyFound(
        int   $enemyId,
        float $distanceBetweenEnemies,
    ): void;

    public function findUnkilledEnemyForNpcAtIngameLocationEnd(): void;

    public function findUnkilledEnemyForNpcAtIngameLocationRetryingWithoutFirstPassExclusions(
        int $npcId,
    ): void;

    public function applyBossKillFloorCutoffMinimumFloorIndexRaised(
        int $enemyId,
        int $npcId,
        int $floorId,
        int $minimumFloorIndex,
    ): void;

    /**
     * @param array<int, int> $enemyPackGroups
     */
    public function theBlindingValeBridgeRuleBridgeEnemyPackGroupsBlocked(
        int   $npcId,
        array $enemyPackGroups,
    ): void;

    /**
     * @param array<int, bool> $preferredGroups
     */
    public function findClosestEnemyInPreferredGroupsStart(array $preferredGroups): void;

    public function findClosestEnemyInPreferredGroupsEnd(): void;

    public function findClosestEnemyInPreferredFloorStart(int $floorId): void;

    public function findClosestEnemyInPreferredFloorEnd(): void;

    public function findClosestEnemyInAllFilteredEnemiesStart(): void;

    public function findClosestEnemyInAllFilteredEnemiesEnemyIsNull(
        float $distanceBetweenEnemies,
    ): void;

    public function findClosestEnemyInAllFilteredEnemiesEnemyIsBossIgnoringTooFarAwayCheck(): void;

    public function findClosestEnemyInAllFilteredEnemiesEnemyTooFarAway(
        ?int  $enemyId,
        float $distanceBetweenEnemies,
        int   $maxDistance,
    ): void;

    public function findClosestEnemyInAllFilteredEnemiesEnd(): void;

    public function findClosestEnemyAndDistanceFromList(int $enemiesCount, bool $considerPatrols): void;

    public function findClosestEnemyAndDistanceFromListResult(
        ?int  $enemyId,
        float $distanceBetweenEnemies,
    ): void;

    /**
     * @param array<int, float> $enemyXY
     * @param array<int, float> $targetEnemyXY
     */
    public function findClosestEnemyAndDistanceDistanceBetweenEnemies(
        array $enemyXY,
        array $targetEnemyXY,
        float $distanceBetweenEnemies,
        float $closestEnemyDistanceBetweenEnemies,
    ): void;
}
