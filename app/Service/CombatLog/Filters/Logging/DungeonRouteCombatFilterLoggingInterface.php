<?php

namespace App\Service\CombatLog\Filters\Logging;

interface DungeonRouteCombatFilterLoggingInterface
{
    public function parseChallengeModeStartFound(int $lineNr): void;
}
