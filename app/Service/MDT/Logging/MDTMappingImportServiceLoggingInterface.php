<?php

namespace App\Service\MDT\Logging;

use Exception;

interface MDTMappingImportServiceLoggingInterface
{

    public function importMappingVersionFromMDTMappingChanged(?string $mdtMappingHash, string $latestMdtMappingHash): void;

    public function importMappingVersionFromMDTCreateMappingVersion(int $version, int $id): void;

    public function importMappingVersionFromMDTStart(int $dungeonId): void;

    public function importMappingVersionFromMDTEnd(): void;

    public function importDungeonStart(): void;

    public function importDungeonTotalCounts(int $mdtDungeonID, int $normal, int $teeming): void;

    public function importDungeonOK(): void;

    public function importDungeonFailed(): void;

    public function importDungeonEnd(): void;

    public function importNpcsStart(): void;

    public function importNpcsSaveNewNpc(int $npcId): void;

    public function importNpcsUpdateExistingNpc(int $npcId): void;

    public function importNpcsSaveNpcException(Exception $exception): void;

    public function importNpcsEnd(): void;

    public function importEnemiesStart(): void;

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

    public function importEnemyPatrolsEnemyHasPatrol(string $uniqueKey): void;

    public function importEnemyPatrolsFoundPatrolIsEmpty(string $uniqueKey): void;

    public function importEnemyPatrolsSaveNewPolyline(int $polylineId): void;

    public function importEnemyPatrolsSaveNewEnemyPatrol(int $enemyPatrolId): void;

    public function importEnemyPatrolsCoupleEnemyPatrolToPolyline(int $enemyPatrolId, int $polylineId): void;

    public function importEnemyPatrolsCoupleEnemiesToEnemyPatrol(int $enemyPatrolId): void;

    public function importEnemyPatrolsEnd(): void;

    public function importDungeonFloorSwitchMarkersStart(): void;

    public function importDungeonFloorSwitchMarkersNewDungeonFloorSwitchMarkerOK(int $dungeonFloorSwitchMarkerId, int $floorId, int $targetFloorId): void;

    public function importDungeonFloorSwitchMarkersEnd(): void;
}
