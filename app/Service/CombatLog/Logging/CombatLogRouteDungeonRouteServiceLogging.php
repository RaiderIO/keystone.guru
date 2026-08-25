<?php

namespace App\Service\CombatLog\Logging;

use App\Logging\Concerns\InteractsWithRollbar;
use App\Logging\StructuredLogging;

class CombatLogRouteDungeonRouteServiceLogging extends StructuredLogging implements CombatLogRouteDungeonRouteServiceLoggingInterface
{
    use InteractsWithRollbar;

    public function getCombatLogRouteStart(string $combatLogFilePath): void
    {
        $this->start(__METHOD__, get_defined_vars());
    }

    public function getCombatLogRouteUnableToGenerateDungeonRouteFromDungeonOrRaid(): void
    {
        $this->warning(__METHOD__);
    }

    public function getCombatLogRouteUnableToGenerateDungeonRouteFromChallengeMode(): void
    {
        $this->warning(__METHOD__);
    }

    public function getCombatLogRouteEnemyEngagedInvalidNpcId(int $npcId): void
    {
        $this->info(__METHOD__, get_defined_vars());
    }

    public function getCombatLogRouteEnemyKilledInvalidNpcId(int $npcId): void
    {
        $this->info(__METHOD__, get_defined_vars());
    }

    public function getCombatLogRoutePlayerDiedUnableToFindCombatantInfo(string $playerGuid): void
    {
        $this->warning(__METHOD__, get_defined_vars());
    }

    public function getCombatLogRouteEnd(): void
    {
        $this->end(__METHOD__);
    }

    public function saveChallengeModeRunUnableToFindFloor(int $uiMapId): void
    {
        $this->warning(__METHOD__, get_defined_vars());
    }

    public function generateMapIconsUnableToFindFloor(string $uniqueId): void
    {
        $this->warning(__METHOD__, get_defined_vars());
    }

    public function generateMapIconsUnableToCalculateMapLocation(string $uniqueId, int $floorId): void
    {
        $this->warning(__METHOD__, get_defined_vars());
    }

    public function saveCombatLogRouteEnemyFailuresUnableToCalculateMapLocation(int $dungeonRouteId, ?int $npcId, int $floorId): void
    {
        $this->warning(__METHOD__, get_defined_vars());
    }

    public function saveCombatLogRouteEnemyFailuresSkippingZeroEnemyForcesNpc(int $dungeonRouteId, int $npcId): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    public function convertCombatLogRouteToDungeonRouteBuildFailedDeletingNewRoute(int $dungeonRouteId, string $exception): void
    {
        $this->warning(__METHOD__, get_defined_vars());
    }

    public function convertCombatLogRouteToDungeonRouteDiscardingAbandonedDraft(int $dungeonRouteId, int $draftDungeonRouteId): void
    {
        $this->warning(__METHOD__, get_defined_vars());
    }

    public function applyRegeneratedDungeonRouteDraftTakenOver(string $publicKey, int $dungeonRouteId, int $draftDungeonRouteId): void
    {
        $this->warning(__METHOD__, get_defined_vars());
    }

    public function applyRegeneratedDungeonRouteApplied(string $publicKey, int $dungeonRouteId, int $draftDungeonRouteId): void
    {
        $this->info(__METHOD__, get_defined_vars());
    }
}
