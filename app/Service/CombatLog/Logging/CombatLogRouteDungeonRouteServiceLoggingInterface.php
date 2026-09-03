<?php

namespace App\Service\CombatLog\Logging;

interface CombatLogRouteDungeonRouteServiceLoggingInterface
{
    public function getCombatLogRouteStart(string $combatLogFilePath): void;

    public function getCombatLogRouteUnableToGenerateDungeonRouteFromDungeonOrRaid(): void;

    public function getCombatLogRouteUnableToGenerateDungeonRouteFromChallengeMode(): void;

    public function getCombatLogRouteEnemyEngagedInvalidNpcId(int $npcId): void;

    public function getCombatLogRouteEnemyKilledInvalidNpcId(int $npcId): void;

    public function getCombatLogRoutePlayerDiedUnableToFindCombatantInfo(string $playerGuid): void;

    public function getCombatLogRouteEnd(): void;

    public function saveChallengeModeRunUnableToFindFloor(int $uiMapId): void;

    public function generateMapIconsUnableToFindFloor(string $uniqueId): void;

    public function generateMapIconsUnableToCalculateMapLocation(string $uniqueId, int $floorId): void;

    public function saveCombatLogRouteEnemyFailuresUnableToCalculateMapLocation(int $dungeonRouteId, ?int $npcId, int $floorId): void;

    public function saveCombatLogRouteEnemyFailuresSkippingNpcWithoutEnemyForces(int $dungeonRouteId, int $npcId): void;

    public function convertCombatLogRouteToDungeonRouteBuildFailedDeletingNewRoute(int $dungeonRouteId, string $exception): void;

    public function convertCombatLogRouteToDungeonRouteDiscardingAbandonedDraft(int $dungeonRouteId, int $draftDungeonRouteId): void;

    public function applyRegeneratedDungeonRouteDraftTakenOver(string $publicKey, int $dungeonRouteId, int $draftDungeonRouteId): void;

    public function applyRegeneratedDungeonRouteApplied(string $publicKey, int $dungeonRouteId, int $draftDungeonRouteId): void;
}
