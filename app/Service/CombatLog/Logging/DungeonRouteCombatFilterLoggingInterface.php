<?php

namespace App\Service\CombatLog\Logging;

interface DungeonRouteCombatFilterLoggingInterface
{
    public function parseChallengeModeStartFound(int $lineNr): void;
}
