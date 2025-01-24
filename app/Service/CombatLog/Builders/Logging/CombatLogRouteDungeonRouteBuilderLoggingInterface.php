<?php

namespace App\Service\CombatLog\Builders\Logging;

interface CombatLogRouteDungeonRouteBuilderLoggingInterface extends DungeonRouteBuilderLoggingInterface
{
    public function buildKillZonesFloorNotFound(?int $currentFloorId, int $uiMapId, int $dungeonId): void;

    public function buildKillZonesFloorAssigningDefaultFloor(int $currentFloorId): void;

    public function buildKillZonesNewCurrentFloor(int $floorId, int $uiMapId): void;

    public function buildKillZonesCreateNewActivePull(): void;

    public function buildKillZonesCreateNewActivePullChainPullCompleted(): void;

    public function buildKillZonesCreateNewActiveChainPull(float $activePullAverageHPPercent, int $chainPullDetectionHPPercent): void;

    public function buildKillZonesUnableToFindEnemyForNpc(string $uniqueUid): void;

    public function buildKillZonesEnemyEngaged(string $uniqueUid, string $timestamp): void;

    public function buildKillZonesEnemyKilled(string $uniqueUid, string $timestamp): void;

    public function buildKillZonesNotAllSpellsAssigned(int $totalAssignedSpells, int $totalSpells): void;

    public function buildKillZonesCreateNewFinalPull(array $guids): void;

    public function determineSpellsCastBetweenInvalidSpellIdBetween(int $spellId): void;

    public function determineSpellsCastBetweenInvalidSpellIdAfter(int $spellId): void;
}
