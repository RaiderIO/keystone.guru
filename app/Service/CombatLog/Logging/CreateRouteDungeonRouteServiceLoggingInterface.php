<?php

namespace App\Service\CombatLog\Logging;

interface CreateRouteDungeonRouteServiceLoggingInterface
{
    /**
     * @return void
     */
    public function getCreateRouteBodyStart(string $combatLogFilePath): void;

    /**
     * @return void
     */
    public function getCreateRouteBodyEnemyEngagedInvalidNpcId(int $npcId): void;

    /**
     * @return void
     */
    public function getCreateRouteBodyEnemyKilledInvalidNpcId(int $npcId): void;

    /**
     * @return void
     */
    public function getCreateRouteBodyEnd(): void;

    /**
     * @return void
     */
    public function saveChallengeModeRunUnableToFindFloor(int $uiMapId): void;

    /**
     * @return void
     */
    public function generateMapIconsUnableToFindFloor(string $uniqueId): void;
}
