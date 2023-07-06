<?php

namespace App\Service\CombatLog\Logging;

interface CreateRouteDungeonRouteServiceLoggingInterface
{
    /**
     * @param string $combatLogFilePath
     *
     * @return void
     */
    public function getCreateRouteBodyStart(string $combatLogFilePath): void;

    /**
     * @param int $npcId
     * @return void
     */
    public function getCreateRouteBodyEnemyEngagedInvalidNpcId(int $npcId): void;

    /**
     * @param int $npcId
     * @return void
     */
    public function getCreateRouteBodyEnemyKilledInvalidNpcId(int $npcId): void;

    /**
     * @return void
     */
    public function getCreateRouteBodyEnd(): void;

    /**
     * @param int $uiMapId
     * @return void
     */
    public function saveChallengeModeRunUnableToFindFloor(int $uiMapId): void;
}
