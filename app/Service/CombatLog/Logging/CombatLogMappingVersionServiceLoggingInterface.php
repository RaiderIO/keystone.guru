<?php

namespace App\Service\CombatLog\Logging;

interface CombatLogMappingVersionServiceLoggingInterface
{
    /**
     * @param string $filePath
     * @return void
     */
    public function createMappingVersionFromChallengeModeStart(string $filePath): void;

    /**
     * @return void
     */
    public function createMappingVersionFromChallengeModeNoChallengeModesFound(): void;

    /**
     * @return void
     */
    public function createMappingVersionFromChallengeModeMultipleChallengeModesFound(): void;

    /**
     * @param string $filePath
     * @return void
     */
    public function createMappingVersionFromDungeonOrRaidStart(string $filePath): void;

    /**
     * @return void
     */
    public function createMappingVersionFromDungeonOrRaidEnd(): void;

    /**
     * @return void
     */
    public function createMappingVersionFromCombatLogTimestampNotSet(): void;

    /**
     * @param int $dungeonId
     * @return void
     */
    public function createMappingVersionFromCombatLogDungeonFromExistingMappingVersion(int $dungeonId): void;

    /**
     * @return void
     */
    public function createMappingVersionFromCombatLogSkipEntryNoDungeon(): void;

    /**
     * @param int $previousFloorId
     * @param int $currentFloorId
     *
     * @return void
     */
    public function createMappingVersionFromCombatLogAddedNewFloorConnection(int $previousFloorId, int $currentFloorId): void;

    /**
     * @return void
     */
    public function createMappingVersionFromCombatLogSkipEntryNoFloor(): void;

    /**
     * @param int $floorId
     * @param int $npcId
     * @return void
     */
    public function createMappingVersionFromCombatLogUnableToFindNpc(int $floorId, int $npcId): void;

    /**
     * @param int $floorId
     * @param int $npcId
     * @return void
     */
    public function createMappingVersionFromCombatLogSkipEnemyIsCritter(int $floorId, int $npcId): void;

    /**
     * @param int $floorId
     * @param int $npcId
     * @return mixed
     */
    public function createMappingVersionFromCombatLogNewEnemy(int $floorId, int $npcId);

    /**
     * @return void
     */
    public function createMappingVersionFromChallengeModeEnd(): void;
}
