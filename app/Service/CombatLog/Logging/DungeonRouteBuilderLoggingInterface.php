<?php

namespace App\Service\CombatLog\Logging;

use Exception;

interface DungeonRouteBuilderLoggingInterface
{
    public function buildStart(string $toDateTimeString, string $eventName): void;

    public function findFloorByUiMapIdNoFloorFound(Exception $exception, int $uitMapId): void;

    public function buildNoFloorFoundYet(): void;

    public function buildChallengeModeEnded(): void;

    public function buildInCombatWithEnemy(string $guid): void;

    public function buildUnitDiedNoLongerInCombat(string $guid): void;

    public function buildUnitDiedNotInCombat(string $guid): void;

    public function buildCreateNewPull(array $keys): void;

    public function createPullFindEnemyForGuidStart(string $guid): void;

    public function createPullEnemyNotFound(int $npcId, float $ingameX, float $ingameY): void;

    public function createPullFindEnemyForGuidEnd(): void;

    public function createPullEnemyAttachedToKillZone(int $npcId, float $ingameX, float $ingameY): void;

    public function buildCreateNewFinalPull(array $keys): void;

    public function buildEnd(): void;
    
    public function createPullFindEnemyForGuidStartMappingToDifferentNpcId(int $npcId, int $targetNpcId): void;
    
    public function findUnkilledEnemyForNpcAtIngameLocationStart(int $npcId, float $ingameX, float $ingameY): void;
    
    public function findUnkilledEnemyForNpcAtIngameLocationEnemyFoundInPreferredGroup(int $id, int $closestEnemyDistance, int $group): void;
    
    public function findUnkilledEnemyForNpcAtIngameLocationClosestEnemy(?int $enemyId, float $closestEnemyDistance): void;
    
    public function findUnkilledEnemyForNpcAtIngameLocationConsideringPatrols(): void;
    
    public function findUnkilledEnemyForNpcAtIngameLocationEnemyIsBossIgnoringTooFarAwayCheck(): void;
    
    public function findUnkilledEnemyForNpcAtIngameLocationEnemyTooFarAway(?int $enemyId, float $closestEnemyDistance, int $maxDistance): void;
    
    public function findUnkilledEnemyForNpcAtIngameLocationEnemyFound(int $enemyId, float $closestEnemyDistance): void;
    
    public function findUnkilledEnemyForNpcAtIngameLocationEnd(): void;
}
