<?php

namespace App\Service\CombatLog\Logging;

interface DungeonRouteCombatFilterLoggingInterface
{
    /**
     * @param int $lineNr
     * @return void
     */
    public function parseChallengeModeStartFound(int $lineNr): void;
}
