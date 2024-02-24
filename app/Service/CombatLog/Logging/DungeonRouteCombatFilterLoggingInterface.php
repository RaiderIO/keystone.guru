<?php

namespace App\Service\CombatLog\Logging;

interface DungeonRouteCombatFilterLoggingInterface
{
    /**
     * @return void
     */
    public function parseChallengeModeStartFound(int $lineNr): void;
}
