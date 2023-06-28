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
     * @return void
     */
    public function getCreateRouteBodyEnd(): void;
}