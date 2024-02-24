<?php

namespace App\Service\CombatLog\Logging;

interface CombatLogMappingVersionServiceLoggingInterface
{
    /**
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
     * @return void
     */
    public function createMappingVersionFromCombatLogDungeonFromExistingMappingVersion(int $dungeonId): void;

    /**
     * @return void
     */
    public function createMappingVersionFromCombatLogSkipEntryNoDungeon(): void;

    /**
     *
     * @return void
     */
    public function createMappingVersionFromCombatLogAddedNewFloorConnection(int $previousFloorId, int $currentFloorId): void;

    /**
     * @return void
     */
    public function createMappingVersionFromCombatLogSkipEntryNoFloor(): void;

    /**
     * @return void
     */
    public function createMappingVersionFromCombatLogUnableToFindNpc(int $floorId, int $npcId): void;

    /**
     * @return void
     */
    public function createMappingVersionFromCombatLogSkipEnemyIsCritter(int $floorId, int $npcId): void;

    /**
     * @return mixed
     */
    public function createMappingVersionFromCombatLogNewEnemy(int $floorId, int $npcId);

    /**
     * @return void
     */
    public function createMappingVersionFromChallengeModeEnd(): void;
}
