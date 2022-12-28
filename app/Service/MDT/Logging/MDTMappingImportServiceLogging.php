<?php

namespace App\Service\MDT\Logging;

use App\Logging\StructuredLogging;
use Exception;

class MDTMappingImportServiceLogging extends StructuredLogging implements MDTMappingImportServiceLoggingInterface
{
    public function __construct()
    {
        $this->setChannel('stderr');
    }

    /**
     * @param string|null $mdtMappingHash
     * @param string $latestMdtMappingHash
     * @return void
     */
    public function importMappingVersionFromMDTMappingChanged(?string $mdtMappingHash, string $latestMdtMappingHash): void
    {
        $this->info(__METHOD__, get_defined_vars());
    }

    /**
     * @param int $version
     * @param int $id
     * @return void
     */
    public function importMappingVersionFromMDTCreateMappingVersion(int $version, int $id): void
    {
        $this->info(__METHOD__, get_defined_vars());
    }

    /**
     * @param int $dungeonId
     * @return void
     */
    public function importMappingVersionFromMDTStart(int $dungeonId): void
    {
        $this->start(__METHOD__, get_defined_vars());
    }

    /**
     * @return void
     */
    public function importMappingVersionFromMDTEnd(): void
    {
        $this->end(__METHOD__, get_defined_vars());
    }

    /**
     * @return void
     */
    public function importDungeonStart(): void
    {
        $this->start(__METHOD__, get_defined_vars());
    }

    /**
     * @param int $mdtDungeonID
     * @param int $normal
     * @param int $teeming
     * @return void
     */
    public function importDungeonTotalCounts(int $mdtDungeonID, int $normal, int $teeming): void
    {
        $this->info(__METHOD__, get_defined_vars());
    }

    /**
     * @return void
     */
    public function importDungeonOK(): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    /**
     * @return void
     */
    public function importDungeonFailed(): void
    {
        $this->error(__METHOD__, get_defined_vars());
    }

    /**
     * @return void
     */
    public function importDungeonEnd(): void
    {
        $this->end(__METHOD__, get_defined_vars());
    }

    /**
     * @return void
     */
    public function importNpcsStart(): void
    {
        $this->start(__METHOD__, get_defined_vars());
    }

    /**
     * @param int $npcId
     * @return void
     */
    public function importNpcsSaveNewNpc(int $npcId): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    /**
     * @param int $npcId
     * @return void
     */
    public function importNpcsUpdateExistingNpc(int $npcId): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    /**
     * @param Exception $exception
     * @return void
     */
    public function importNpcsSaveNpcException(Exception $exception): void
    {
        $this->error(__METHOD__, get_defined_vars());
    }

    /**
     * @return void
     */
    public function importNpcsEnd(): void
    {
        $this->end(__METHOD__, get_defined_vars());
    }

    /**
     * @return void
     */
    public function importEnemiesStart(): void
    {
        $this->start(__METHOD__, get_defined_vars());
    }

    /**
     * @param string $uniqueKey
     * @param array $updatedFields
     * @return void
     */
    public function importEnemiesRecoverPropertiesFromExistingEnemy(string $uniqueKey, array $updatedFields): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    /**
     * @param string $uniqueKey
     * @return void
     */
    public function importEnemiesCannotRecoverPropertiesFromExistingEnemy(string $uniqueKey): void
    {
        $this->error(__METHOD__, get_defined_vars());
    }

    /**
     * @param int $enemyId
     * @return void
     */
    public function importEnemiesSaveNewEnemy(int $enemyId): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    /**
     * @param Exception $exception
     * @return void
     */
    public function importEnemiesSaveNewEnemyException(Exception $exception): void
    {
        $this->error(__METHOD__, get_defined_vars());
    }

    /**
     * @return void
     */
    public function importEnemiesEnd(): void
    {
        $this->end(__METHOD__, get_defined_vars());
    }

    /**
     * @return void
     */
    public function importEnemyPacksStart(): void
    {
        $this->start(__METHOD__, get_defined_vars());
    }

    /**
     * @param int $enemyPackId
     * @param int $count
     * @return void
     */
    public function importEnemyPacksSaveNewEnemyPackOK(int $enemyPackId, int $count): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    /**
     * @param int $enemyPackId
     * @return void
     */
    public function importEnemyPacksCoupleEnemyToPackStart(int $enemyPackId): void
    {
        $this->start(__METHOD__, get_defined_vars());
    }

    /**
     * @param int $enemyId
     * @return void
     */
    public function importEnemyPacksCoupleEnemyToEnemyPack(int $enemyId): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    /**
     * @return void
     */
    public function importEnemyPacksCoupleEnemyToPackEnd(): void
    {
        $this->end(__METHOD__, get_defined_vars());
    }

    /**
     * @return void
     */
    public function importEnemyPacksEnd(): void
    {
        $this->end(__METHOD__, get_defined_vars());
    }

    /**
     * @return void
     */
    public function importEnemyPatrolsStart(): void
    {
        $this->start(__METHOD__, get_defined_vars());
    }

    /**
     * @param string $uniqueKey
     * @return void
     */
    public function importEnemyPatrolsEnemyHasPatrol(string $uniqueKey): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    /**
     * @param string $uniqueKey
     * @return void
     */
    public function importEnemyPatrolsFoundPatrolIsEmpty(string $uniqueKey): void
    {
        $this->warning(__METHOD__, get_defined_vars());
    }

    /**
     * @param int $polylineId
     * @return void
     */
    public function importEnemyPatrolsSaveNewPolyline(int $polylineId): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    /**
     * @param int $enemyPatrolId
     * @return void
     */
    public function importEnemyPatrolsSaveNewEnemyPatrol(int $enemyPatrolId): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    /**
     * @param int $enemyPatrolId
     * @param int $polylineId
     * @return void
     */
    public function importEnemyPatrolsCoupleEnemyPatrolToPolyline(int $enemyPatrolId, int $polylineId): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    /**
     * @param int $enemyPatrolId
     * @return void
     */
    public function importEnemyPatrolsCoupleEnemiesToEnemyPatrol(int $enemyPatrolId): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    /**
     * @return void
     */
    public function importEnemyPatrolsEnd(): void
    {
        $this->end(__METHOD__, get_defined_vars());
    }

    /**
     * @return void
     */
    public function importDungeonFloorSwitchMarkersStart(): void
    {
        $this->start(__METHOD__, get_defined_vars());
    }

    /**
     * @param int $dungeonFloorSwitchMarkerId
     * @param int $floorId
     * @param int $targetFloorId
     * @return void
     */
    public function importDungeonFloorSwitchMarkersNewDungeonFloorSwitchMarkerOK(int $dungeonFloorSwitchMarkerId, int $floorId, int $targetFloorId): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    /**
     * @return void
     */
    public function importDungeonFloorSwitchMarkersEnd(): void
    {
        $this->end(__METHOD__, get_defined_vars());
    }
}
