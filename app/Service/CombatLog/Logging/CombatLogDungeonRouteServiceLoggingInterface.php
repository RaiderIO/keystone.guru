<?php

namespace App\Service\CombatLog\Logging;

interface CombatLogDungeonRouteServiceLoggingInterface
{

    public function convertCombatLogToDungeonRoutesStart(string $combatLogFilePath): void;

    public function convertCombatLogToDungeonRoutesEnd(): void;

    public function saveEnemyPositionFromResultEventsStart(): void;

    public function saveEnemyPositionFromResultEventsEnd(): void;
}
