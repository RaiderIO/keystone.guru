<?php

namespace App\Service\CombatLog\Logging;

use App\Logging\StructuredLogging;

class CombatLogMappingVersionServiceLogging extends StructuredLogging implements CombatLogMappingVersionServiceLoggingInterface
{
    /**
     * {@inheritDoc}
     */
    public function createMappingVersionFromChallengeModeStart(string $filePath): void
    {
        $this->start(__METHOD__, get_defined_vars());
    }

    /**
     * {@inheritDoc}
     */
    public function createMappingVersionFromChallengeModeNoChallengeModesFound(): void
    {
        $this->debug(__METHOD__);
    }

    /**
     * {@inheritDoc}
     */
    public function createMappingVersionFromChallengeModeMultipleChallengeModesFound(): void
    {
        $this->debug(__METHOD__);
    }

    /**
     * {@inheritDoc}
     */
    public function createMappingVersionFromChallengeModeEnd(): void
    {
        $this->end(__METHOD__);
    }

    public function createMappingVersionFromDungeonOrRaidStart(string $filePath): void
    {
        $this->start(__METHOD__, get_defined_vars());
    }

    public function createMappingVersionFromDungeonOrRaidEnd(): void
    {
        $this->end(__METHOD__);
    }

    /**
     * {@inheritDoc}
     */
    public function createMappingVersionFromCombatLogTimestampNotSet(): void
    {
        $this->debug(__METHOD__);
    }

    public function createMappingVersionFromCombatLogDungeonFromExistingMappingVersion(int $dungeonId): void
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

    /**
     * @return void
     */
    public function createMappingVersionFromCombatLogNewEnemy(int $floorId, int $npcId): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }
}
