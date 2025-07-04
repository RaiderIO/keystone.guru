<?php

namespace App\Service\MDT\Logging;

use App\Logging\RollbarStructuredLogging;
use Exception;

class MDTMappingImportServiceLogging extends RollbarStructuredLogging implements MDTMappingImportServiceLoggingInterface
{
    public function importMappingVersionFromMDTMappingChanged(?string $mdtMappingHash, string $latestMdtMappingHash): void
    {
        $this->info(__METHOD__, get_defined_vars());
    }

    public function importMappingVersionFromMDTCreateMappingVersion(int $version, int $id): void
    {
        $this->info(__METHOD__, get_defined_vars());
    }

    public function importMappingVersionFromMDTStart(int $dungeonId): void
    {
        $this->start(__METHOD__, get_defined_vars());
    }

    public function importMappingVersionFromMDTEnd(): void
    {
        $this->end(__METHOD__);
    }

    public function importDungeonMappingVersionFromMDTNoChangeDetected(string $key, ?string $latestMdtMappingHash): void
    {
        $this->warning(__METHOD__, get_defined_vars());
    }

    public function importDungeonStart(): void
    {
        $this->start(__METHOD__);
    }

    public function importDungeonTotalCounts(int $mdtDungeonID, int $normal, int $teeming): void
    {
        $this->info(__METHOD__, get_defined_vars());
    }

    public function importDungeonOK(): void
    {
        $this->debug(__METHOD__);
    }

    public function importDungeonFailed(): void
    {
        $this->error(__METHOD__);
    }

    public function importDungeonEnd(): void
    {
        $this->end(__METHOD__);
    }

    public function importNpcsDataFromMDTStart(string $key): void
    {
        $this->start(__METHOD__, get_defined_vars());
    }

    public function importNpcsDataFromMDTIgnoreNpc(int $npcId): void
    {
        $this->info(__METHOD__, get_defined_vars());
    }

    public function importNpcsDataFromMDTNpcNotMarkedForAllDungeons(int $npcId): void
    {
        $this->error(__METHOD__, get_defined_vars());
    }

    public function importNpcsDataFromMDTSaveNpcException(Exception $exception): void
    {
        $this->error(__METHOD__, get_defined_vars());
    }

    public function importNpcsDataFromMDTCharacteristicsAndSpellsUpdate(
        int $npcsUpdated,
        int $npcsInserted,
        int $npcCharacteristicsDeleted,
        int $npcCharacteristicsInserted,
        int $npcSpellsDeleted,
        int $npcSpellsInserted,
        int $npcDungeonsDeleted,
        int $npcDungeonsInserted
    ): void {
        $this->debug(__METHOD__, get_defined_vars());
    }

    public function importNpcsDataFromMDTEnd(): void
    {
        $this->end(__METHOD__);
    }

    public function importSpellDataFromMDTStart(string $key): void
    {
        $this->start(__METHOD__, get_defined_vars());
    }

    public function importSpellDataFromMDTSpellInExcludeList(): void
    {
        $this->debug(__METHOD__);
    }


    public function importSpellDataFromMDTResult(int $spellCount, int $spellDungeonCount): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    public function importSpellDataFromMDTFailed(): void
    {
        $this->error(__METHOD__);
    }

    public function importSpellDataFromMDTEnd(): void
    {
        $this->end(__METHOD__);
    }

    public function importNpcsStart(): void
    {
        $this->start(__METHOD__);
    }

    public function importNpcsDataFromMDTUnableToFindCharacteristicForNpc(int $id, string $characteristicName): void
    {
        $this->error(__METHOD__, get_defined_vars());
    }

    public function importNpcsDataFromMDTSpellInExcludeList(): void
    {
        $this->debug(__METHOD__);
    }

    public function importNpcsDataFromMDTSaveNewNpc(int $npcId): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    public function importNpcsUnableToFindNpc(int $npcId): void
    {
        $this->warning(__METHOD__, get_defined_vars());
    }

    public function importNpcsUpdateExistingNpc(int $npcId): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    public function importNpcsEnd(): void
    {
        $this->end(__METHOD__);
    }

    public function importEnemiesStart(): void
    {
        $this->start(__METHOD__);
    }

    public function importEnemiesSkipIgnoredByNpcEnemy(string $uniqueKey): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    public function importEnemiesSkipTeemingEnemy(string $uniqueKey): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    public function importEnemiesDistanceTooLargeNotTransferringExistingEnemyLatLng(string $mdtUniqueKey, float $distance): void
    {
        $this->warning(__METHOD__, get_defined_vars());
    }

    public function importEnemiesRecoverPropertiesFromExistingEnemy(string $uniqueKey, array $updatedFields): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    public function importEnemiesCannotRecoverPropertiesFromExistingEnemy(string $uniqueKey): void
    {
        $this->error(__METHOD__, get_defined_vars());
    }

    public function importEnemiesSaveNewEnemy(int $enemyId): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    public function importEnemiesSaveNewEnemyException(Exception $exception): void
    {
        $this->error(__METHOD__, get_defined_vars());
    }

    public function importEnemiesEnd(): void
    {
        $this->end(__METHOD__);
    }

    public function importEnemyPacksStart(): void
    {
        $this->start(__METHOD__);
    }

    public function importEnemyPacksSaveNewEnemyPackOK(int $enemyPackId, int $count): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    public function importEnemyPacksCoupleEnemyToPackStart(int $enemyPackId): void
    {
        $this->start(__METHOD__, get_defined_vars());
    }

    public function importEnemyPacksCoupleEnemyToEnemyPack(int $enemyId): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    public function importEnemyPacksCoupleEnemyToPackEnd(): void
    {
        $this->end(__METHOD__);
    }

    public function importEnemyPacksEnd(): void
    {
        $this->end(__METHOD__);
    }

    public function importEnemyPatrolsStart(): void
    {
        $this->start(__METHOD__);
    }

    public function importEnemyPatrolsUnableToFindAttachedEnemy(int $mdtCloneIndex, array $mdtNpcClone, int $npcId, int $mdtId): void
    {
        $this->error(__METHOD__, get_defined_vars());
    }

    public function importEnemyPatrolsEnemyHasPatrol(string $uniqueKey): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    public function importEnemyPatrolsFoundPatrolIsEmpty(string $uniqueKey): void
    {
        $this->warning(__METHOD__, get_defined_vars());
    }

    public function importEnemyPatrolsSaveNewPolyline(int $polylineId): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    public function importEnemyPatrolsSaveNewEnemyPatrol(int $enemyPatrolId): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    public function importEnemyPatrolsCoupleEnemyPatrolToPolyline(int $enemyPatrolId, int $polylineId): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    public function importEnemyPatrolsCoupleEnemiesToEnemyPatrol(int $enemyPatrolId): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    public function importEnemyPatrolsEnd(): void
    {
        $this->end(__METHOD__);
    }

    public function importMapPOIsStart(): void
    {
        $this->start(__METHOD__);
    }

    public function importMapPOIsMDTHasMapPOIs(): void
    {
        $this->debug(__METHOD__);
    }

    public function importMapPOIsCreatedNewMapIcon(int $mapIconId, int $floorId, int $mapIconTypeId): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    public function importMapPOIsMapIconAlreadyExists(int $mapIconId, array $latLng, string $mdtMapPOIName): void
    {
        $this->info(__METHOD__, get_defined_vars());
    }

    public function importMapPOIsNewDungeonFloorSwitchMarkerOK(int $dungeonFloorSwitchMarkerId, int $floorId, int $targetFloorId): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    public function importMapPOIsHaveExistingFloorSwitchMarkers(int $count): void
    {
        $this->info(__METHOD__, get_defined_vars());
    }

    public function importMapPOIsEnd(): void
    {
        $this->end(__METHOD__);
    }
}
