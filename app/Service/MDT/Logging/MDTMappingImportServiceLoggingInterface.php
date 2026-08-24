<?php

namespace App\Service\MDT\Logging;

use Exception;
use Throwable;

interface MDTMappingImportServiceLoggingInterface
{
    public function importMappingVersionFromMDTMappingChanged(
        ?string $mdtMappingHash,
        string  $latestMdtMappingHash,
    ): void;

    public function importMappingVersionFromMDTCreateMappingVersion(int $version, int $id): void;

    public function importMappingVersionFromMDTStart(int $dungeonId): void;

    /**
     * The mapping version is not safe to leave behind half-built, so it is deleted before the exception is
     * rethrown - see the catch block in importMappingVersionFromMDT() (#3737).
     */
    public function importMappingVersionFromMDTDeletePartialMappingVersion(int $version, int $id, Throwable $throwable): void;

    public function importMappingVersionFromMDTEnd(): void;

    public function importDungeonMappingVersionFromMDTNoChangeDetected(
        string  $key,
        ?string $latestMdtMappingHash,
    ): void;

    public function importDungeonStart(): void;

    public function importDungeonTotalCounts(int $mdtDungeonID, int $normal, int $teeming): void;

    public function importDungeonOK(): void;

    public function importDungeonFailed(): void;

    public function importDungeonEnd(): void;

    public function importNpcsDataFromMDTStart(string $key): void;

    public function importNpcsDataFromMDTIgnoreNpc(int $npcId): void;

    public function importNpcsDataFromMDTSkipHealthOverwrite(int $npcId, int $existingHealth, int $mdtHealth): void;

    public function importNpcsDataFromMDTNpcsUpdate(
        int $npcsUpdated,
        int $npcsInserted,
        int $npcDungeonsInserted,
    ): void;

    public function importNpcsDataFromMDTNpcNotMarkedForAllDungeons(int $npcId): void;

    public function importNpcsDataFromMDTSaveNpcException(Exception $exception): void;

    public function importNpcsDataFromMDTEnd(): void;

    public function importNpcsStart(): void;

    public function importNpcsDataFromMDTSaveNewNpc(int $npcId): void;

    public function importNpcsUnableToFindNpc(int $npcId): void;

    public function importNpcsUpdateExistingNpc(int $npcId): void;

    public function importNpcsEnd(): void;

    public function importEnemiesStart(): void;

    public function importEnemiesSkipIgnoredByNpcEnemy(string $uniqueKey): void;

    public function importEnemiesSkipTeemingEnemy(string $uniqueKey): void;

    public function importEnemiesDistanceTooLargeNotTransferringExistingEnemyLatLng(
        string $mdtUniqueKey,
        float  $distance,
    ): void;

    /**
     * @param array<string, mixed> $updatedFields
     */
    public function importEnemiesRecoverPropertiesFromExistingEnemy(string $uniqueKey, array $updatedFields): void;

    public function importEnemiesCannotRecoverPropertiesFromExistingEnemy(string $uniqueKey): void;

    public function importEnemiesCannotRecoverEnemyForcesCheckpoint(string $uniqueKey, int $enemyForcesCheckpointId): void;

    public function importEnemiesDeleteEmptyEnemyForcesCheckpoint(int $enemyForcesCheckpointId, ?string $name): void;

    /**
     * A dungeon-wide summary of the deletions above, logged at a level that reaches Discord: post-#3702 this
     * only fires for checkpoints that genuinely had members in the source mapping version, so it is worth a
     * human's attention.
     */
    public function importEnemiesPrunedEmptyEnemyForcesCheckpoints(int $dungeonId, int $count): void;

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

    /**
     * @param array<int, mixed> $mdtNpcClone
     */
    public function importEnemyPatrolsUnableToFindAttachedEnemy(
        int   $mdtCloneIndex,
        array $mdtNpcClone,
        int   $npcId,
        int   $mdtId,
    ): void;

    public function importEnemyPatrolsEnemyHasPatrol(string $uniqueKey): void;

    public function importEnemyPatrolsFoundPatrolIsEmpty(string $uniqueKey): void;

    public function importEnemyPatrolsFoundExistingEnemyPatrol(int $enemyPatrolId): void;

    public function importEnemyPatrolsSaveNewPolyline(int $polylineId): void;

    public function importEnemyPatrolsSaveNewMdtPolyline(int $polylineId): void;

    public function importEnemyPatrolsSaveNewEnemyPatrol(int $enemyPatrolId): void;

    public function importEnemyPatrolsCoupleEnemyPatrolToPolyline(int $enemyPatrolId, int $polylineId): void;

    public function importEnemyPatrolsCoupleEnemyPatrolToMdtPolyline(int $enemyPatrolId, int $polylineId): void;

    public function importEnemyPatrolsCoupleEnemiesToEnemyPatrol(int $enemyPatrolId): void;

    public function importEnemyPatrolsClonedPatrolWithoutMdtPolyline(?int $newEnemyPatrolId): void;

    public function importEnemyPatrolsEnd(): void;

    public function importMappingVersionFromMDTNpcSetReplaced(
        string $dungeonKey,
        int    $previousNpcCount,
        int    $incomingNpcCount,
        int    $keptPercentage,
        bool   $forceImport,
    ): void;

    public function importMapPOIsStart(): void;

    public function importMapPOIsMDTHasMapPOIs(): void;

    public function importMapPOIsMissingTranslation(string $translationKey): void;

    public function importMapPOIsDeletedClonedGenericItemMapIcons(int $deletedCount): void;

    public function importMapPOIsUnhandledMapPOI(
        string $mdtMapPOIType,
        ?int   $spellId,
        ?int   $textureFileDataId,
        int    $subLevel,
    ): void;

    public function importMapPOIsCreatedNewMapIcon(int $mapIconId, int $floorId, int $mapIconTypeId): void;

    /**
     * @param array<string, mixed> $latLng
     */
    public function importMapPOIsMapIconAlreadyExists(int $mapIconId, array $latLng, string $mdtMapPOIName): void;

    public function importMapPOIsNewDungeonFloorSwitchMarkerOK(
        int $dungeonFloorSwitchMarkerId,
        int $floorId,
        int $targetFloorId,
    ): void;

    public function importMapPOIsHaveExistingFloorSwitchMarkers(int $count): void;

    public function importMapPOIsEnd(): void;
}
