<?php

namespace App\Service\CombatLog\Logging;

interface CombatLogMappingVersionServiceLoggingInterface
{
    public function createMappingVersionFromChallengeModeStart(string $filePath): void;

    public function createMappingVersionFromChallengeModeNoChallengeModesFound(): void;

    public function createMappingVersionFromChallengeModeMultipleChallengeModesFound(): void;

    public function createMappingVersionFromDungeonOrRaidStart(string $filePath): void;

    public function createMappingVersionFromDungeonOrRaidEnd(): void;

    public function createMappingVersionFromCombatLogTimestampNotSet(): void;

    public function createMappingVersionFromCombatLogDungeonFromExistingMappingVersion(int $dungeonId): void;

    public function createMappingVersionFromCombatLogSkipEntryNoDungeon(): void;

    public function createMappingVersionFromCombatLogAddedNewFloorConnection(int $previousFloorId, int $currentFloorId): void;

    public function createMappingVersionFromCombatLogSkipEntryNoFloor(): void;

    public function createMappingVersionFromCombatLogUnableToFindNpc(int $floorId, int $npcId): void;

    public function createMappingVersionFromCombatLogSkipEnemyIsCritter(int $floorId, int $npcId): void;

    /**
     * @return mixed
     */
    public function createMappingVersionFromCombatLogNewEnemy(int $floorId, int $npcId);

    public function createMappingVersionFromChallengeModeEnd(): void;
}
