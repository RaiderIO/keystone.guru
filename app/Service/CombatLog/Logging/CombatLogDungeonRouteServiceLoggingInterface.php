<?php

namespace App\Service\CombatLog\Logging;

interface CombatLogDungeonRouteServiceLoggingInterface
{

    public function getResultEventsStart(string $combatLogFilePath): void;

    public function getResultEventsEnd(): void;

    public function convertCombatLogToDungeonRoutesStart(string $combatLogFilePath): void;

    public function convertCombatLogToDungeonRoutesEnd(): void;

    public function saveEnemyPositionsStart(): void;

    public function saveEnemyPositionsEnd(): void;
}
