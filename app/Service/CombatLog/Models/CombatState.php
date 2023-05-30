<?php

namespace App\Service\CombatLog\Models;

use Illuminate\Support\Collection;

class CombatState
{
    private Collection $currentEnemiesInCombat;

    public function __construct()
    {
        $this->currentEnemiesInCombat = collect();
    }

    public function partyWiped(): void
    {
        $this->currentEnemiesInCombat = collect();
    }
}
