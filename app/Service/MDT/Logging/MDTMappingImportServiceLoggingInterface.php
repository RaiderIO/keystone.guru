<?php

namespace App\Service\MDT\Logging;

use Exception;

interface MDTMappingImportServiceLoggingInterface
{
    public function importMappingVersionFromMDTMappingChanged(?string $mdtMappingHash, string $latestMdtMappingHash): void;

    public function importMappingVersionFromMDTCreateMappingVersion(int $version, int $id): void;

    public function importMappingVersionFromMDTStart(int $dungeonId): void;

    public function importMappingVersionFromMDTEnd(): void;

    public function importDungeonMappingVersionFromMDTNoChangeDetected(string $key, ?string $latestMdtMappingHash): void;

    public function importDungeonStart(): void;

    public function importDungeonTotalCounts(int $mdtDungeonID, int $normal, int $teeming): void;

    public function importDungeonOK(): void;

    public function importDungeonFailed(): void;

    public function importDungeonEnd(): void;

    public function importNpcsDataFromMDTStart(string $key): void;

    public function importNpcsDataFromMDTCharacteristicsAndSpellsUpdate(
        int $npcCharacteristicsDeleted,
        int $npcCharacteristicsInserted,
        int $npcSpellsDeleted,
        int $npcSpellsInserted
    ): void;

    public function importNpcsDataFromMDTNpcNotMarkedForAllDungeons(int $npcId): void;

    public function importNpcsDataFromMDTSaveNpcException(Exception $exception): void;

    public function importNpcsDataFromMDTEnd(): void;

    public function importSpellDataFromMDTStart(string $key): void;

    public function importSpellDataFromMDTSpellInExcludeList(): void;

    public function importSpellDataFromMDTResult(int $spellCount, int $spellDungeonCount): void;

    public function importSpellDataFromMDTFailed(): void;

    public function importSpellDataFromMDTEnd(): void;

    public function importNpcsStart(): void;

    public function importNpcsDataFromMDTUnableToFindCharacteristicForNpc(int $id, string $characteristicName): void;

    public function importNpcsDataFromMDTSpellInExcludeList(): void;

    public function importNpcsDataFromMDTSaveNewNpc(int $npcId): void;

    public function importNpcsUnableToFindNpc(int $npcId): void;

    public function importNpcsUpdateExistingNpc(int $npcId): void;

    public function importNpcsEnd(): void;

    public function importEnemiesStart(): void;

    public function importEnemiesSkipIgnoredByNpcEnemy(string $uniqueKey): void;

    public function importEnemiesSkipTeemingEnemy(string $uniqueKey): void;

    public function importEnemiesDistanceTooLargeNotTransferringExistingEnemyLatLng(string $mdtUniqueKey, float $distance): void;

    public function importEnemiesRecoverPropertiesFromExistingEnemy(string $uniqueKey, array $updatedFields): void;

    public function importEnemiesCannotRecoverPropertiesFromExistingEnemy(string $uniqueKey): void;

    public function importEnemiesSaveNewEnemy(int $enemyId): void;

    public function importEnemiesSaveNewEnemyException(Exception $exception): void;

    public function importEnemiesEnd(): void;

    public function importEnemyPacksStart(): void;

    public function importEnemyPacksSaveNewEnemyPackOK(int $enemyPackId, int $count): void;

    public function importEnemyPacksCoupleEnemyToPackStart(int $enemyPackId): void;

    public function importEnemyPacksCoupleEnemyToEnemyPack(int $enemyId): void;

    public function importEnemyPacksCoupleEnemyToPackEnd(): void;

    public function importEnemyPacksEnd(): void;

    public function importEnemyPatrolsStart(): void;

    public function importEnemyPatrolsUnableToFindAttachedEnemy(int $mdtCloneIndex, array $mdtNpcClone, int $npcId, int $mdtId): void;

    public function importEnemyPatrolsEnemyHasPatrol(string $uniqueKey): void;

    public function importEnemyPatrolsFoundPatrolIsEmpty(string $uniqueKey): void;

    public function importEnemyPatrolsSaveNewPolyline(int $polylineId): void;

    public function importEnemyPatrolsSaveNewEnemyPatrol(int $enemyPatrolId): void;

    public function importEnemyPatrolsCoupleEnemyPatrolToPolyline(int $enemyPatrolId, int $polylineId): void;

    public function importEnemyPatrolsCoupleEnemiesToEnemyPatrol(int $enemyPatrolId): void;

    public function importEnemyPatrolsEnd(): void;

    public function importMapPOIsStart(): void;

    public function importMapPOIsMDTHasMapPOIs(): void;

    public function importMapPOIsCreatedNewMapIcon(int $mapIconId, int $floorId, int $mapIconTypeId): void;

    public function importMapPOIsMapIconAlreadyExists(int $mapIconId, array $latLng, string $mdtMapPOIName): void;

    public function importMapPOIsNewDungeonFloorSwitchMarkerOK(int $dungeonFloorSwitchMarkerId, int $floorId, int $targetFloorId): void;

    public function importMapPOIsHaveExistingFloorSwitchMarkers(int $count): void;

    public function importMapPOIsEnd(): void;
}
