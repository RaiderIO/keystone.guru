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

    public function createPullSpellsAttachedToKillZone(int $killZoneId, array $spellIds, int $spellCount): void;

    public function findUnkilledEnemyForNpcAtIngameLocationMappingToDifferentNpcId(int $npcId, int $targetNpcId): void;

    public function createPullEnd(): void;

    public function findFloorByUiMapIdNoFloorFound(Exception $exception, int $uitMapId): void;

    public function findUnkilledEnemyForNpcAtIngameLocationStart(int $npcId, float $ingameX, float $ingameY, ?float $previousPullLat, ?float $previousPullLng, array $preferredGroups): void;

    public function findUnkilledEnemyForNpcAtIngameLocationEnemyFoundInPreferredGroup(int $id, float $distanceBetweenEnemies, float $distanceBetweenLastPullAndEnemy, int $group): void;

    public function findUnkilledEnemyForNpcAtIngameLocationEnemyFoundInPreferredFloor(int $id, float $distanceBetweenEnemies, float $distanceBetweenLastPullAndEnemy, int $floorId): void;

    public function findClosestEnemyInAllFilteredEnemiesEnemyIsNull(float $distanceBetweenEnemies, float $distanceBetweenLastPullAndEnemy): void;

    public function findClosestEnemyInAllFilteredEnemiesEnemyIsBossIgnoringTooFarAwayCheck(): void;

    public function findClosestEnemyInAllFilteredEnemiesEnemyTooFarAway(?int $enemyId, float $distanceBetweenEnemies, float $distanceBetweenLastPullAndEnemy, int $maxDistance): void;

    public function findUnkilledEnemyForNpcAtIngameLocationEnemyFound(int $enemyId, float $distanceBetweenEnemies, float $distanceBetweenLastPullAndEnemy): void;

    public function findUnkilledEnemyForNpcAtIngameLocationEnd(): void;

    public function findClosestEnemyAndDistanceFromList(int $enemiesCount, bool $considerPatrols): void;

    public function findClosestEnemyAndDistanceFromListPriority(int $killPriority, int $enemyCount): void;

    public function findClosestEnemyAndDistanceFromListFoundEnemy(): void;

    public function findClosestEnemyAndDistanceFromListResult(?int $enemyId, float $distanceBetweenEnemies, float $distanceBetweenLastPullAndEnemy): void;

    public function findClosestEnemyAndDistanceDistanceBetweenEnemies(array $enemyXY, array $targetEnemyXY, float $distanceBetweenEnemies, float $closestEnemyDistanceBetweenEnemies);
}
