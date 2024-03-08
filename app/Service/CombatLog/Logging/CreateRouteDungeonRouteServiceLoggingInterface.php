<?php

namespace App\Service\CombatLog\Logging;

interface CreateRouteDungeonRouteServiceLoggingInterface
{
    public function getCreateRouteBodyStart(string $combatLogFilePath): void;

    public function getCreateRouteBodyEnemyEngagedInvalidNpcId(int $npcId): void;

    public function getCreateRouteBodyEnemyKilledInvalidNpcId(int $npcId): void;

    public function getCreateRouteBodyEnd(): void;

    public function saveChallengeModeRunUnableToFindFloor(int $uiMapId): void;

    public function generateMapIconsUnableToFindFloor(string $uniqueId): void;
}
