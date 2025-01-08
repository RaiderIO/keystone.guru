<?php

namespace App\Service\CombatLog\Logging;

use App\Logging\RollbarStructuredLogging;

class CombatLogMappingVersionServiceLogging extends RollbarStructuredLogging implements CombatLogMappingVersionServiceLoggingInterface
{
    public function createMappingVersionFromChallengeModeStart(string $filePath): void
    {
        $this->start(__METHOD__, get_defined_vars());
    }

    public function createMappingVersionFromChallengeModeNoChallengeModesFound(): void
    {
        $this->debug(__METHOD__);
    }

    public function createMappingVersionFromChallengeModeMultipleChallengeModesFound(): void
    {
        $this->debug(__METHOD__);
    }

    public function createMappingVersionFromChallengeModeEnd(): void
    {
        $this->end(__METHOD__);
    }

    public function createMappingVersionFromDungeonOrRaidStart(string $filePath): void
    {
        $this->start(__METHOD__, get_defined_vars(), false);
    }

    public function createMappingVersionFromDungeonOrRaidEnd(): void
    {
        $this->end(__METHOD__);
    }

    public function createMappingVersionFromCombatLogTimestampNotSet(): void
    {
        $this->debug(__METHOD__);
    }

    public function createMappingVersionFromCombatLogDungeonFromExistingMappingVersion(int $dungeonId): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    public function createMappingVersionFromCombatLogCurrentFloorFromMapChange(int $uiMapId, int $id): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    public function createMappingVersionFromCombatLogCurrentFloorDefaultFloor(int $dungeonId, int $defaultFloorId): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }


    public function createMappingVersionFromCombatLogSkipEntryNoDungeon(): void
    {
        $this->debug(__METHOD__);
    }

    public function createMappingVersionFromCombatLogAddedNewFloorConnection(int $previousFloorId, int $currentFloorId): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    public function createMappingVersionFromCombatLogSkipEntryMapChangeFloorNotFound(): void
    {
        $this->warning(__METHOD__);
    }

    public function createMappingVersionFromCombatLogSkipEntryNoFloor(): void
    {
        $this->debug(__METHOD__);
    }

    public function createMappingVersionFromCombatLogUnableToFindNpc(int $floorId, int $npcId): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    public function createMappingVersionFromCombatLogSkipEnemyIsCritter(int $floorId, int $npcId): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    public function createMappingVersionFromCombatLogNewEnemy(int $floorId, int $npcId): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }
}
