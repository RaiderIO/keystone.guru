<?php

namespace App\Service\CombatLog\Logging;

interface CombatLogRouteDungeonRouteServiceLoggingInterface
{
    public function getCombatLogRouteStart(string $combatLogFilePath): void;

    public function getCombatLogRouteEnemyEngagedInvalidNpcId(int $npcId): void;

    public function getCombatLogRouteEnemyKilledInvalidNpcId(int $npcId): void;

    public function getCombatLogRouteEnd(): void;

    public function saveChallengeModeRunUnableToFindFloor(int $uiMapId): void;

    public function generateMapIconsUnableToFindFloor(string $uniqueId): void;
}
